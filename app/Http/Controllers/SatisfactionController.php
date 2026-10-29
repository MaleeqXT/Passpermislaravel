<?php
namespace App\Http\Controllers;

use App\Http\Requests\SatisfactionSubmitRequest;
use App\Models\{SatisfactionAnswer,SatisfactionNotification,SatisfactionResponse,SatisfactionSurvey};
use App\Models\Roles\Student\User\Student;
use App\Services\SatisfactionLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class SatisfactionController extends Controller
{
    public function available(Request $request, SatisfactionLifecycleService $lifecycle)
    {
        $student = $this->resolveAuthenticatedStudent($request);
        if (!$student) return response()->json(['data' => null]);
        $lifecycle->evaluateAll($student);
        return response()->json(['data' => $this->pendingSurveys($student)->first()]);
    }

    public function pending(Request $request, SatisfactionLifecycleService $lifecycle)
    {
        $student = $this->resolveAuthenticatedStudent($request);
        abort_unless($student, 403);

        $lifecycle->evaluateAll($student);
        return response()->json(['data' => $this->pendingSurveys($student)->values()]);
    }

    public function show(Request $request, SatisfactionSurvey $survey)
    {
        $student = $this->resolveAuthenticatedStudent($request);
        abort_unless($student, 403);

        $response = SatisfactionResponse::query()
            ->where('survey_id', $survey->id)
            ->where('candidate_id', $student->id)
            ->first();
        abort_unless($response, 403);

        $survey->load(['questions' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')]);
        $survey->setAttribute('completed', $response->status === 'completed');
        $survey->setAttribute('response_id', $response->id);
        return response()->json(['data' => $survey]);
    }

    public function stage(Request $request, string $stage)
    {
        abort_unless(in_array($stage, ['before_training', 'during_training', 'after_training'], true), 404);
        $student = $this->resolveAuthenticatedStudent($request);
        abort_unless($student, 403);

        $response = SatisfactionResponse::query()
            ->with(['survey.questions' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->where('candidate_id', $student->id)
            ->whereHas('survey', fn ($query) => $query->where('stage', $stage))
            ->when($request->filled('response'), fn ($query) => $query->whereKey($request->input('response')))
            ->orderBy('created_at')
            ->first();
        abort_unless($response, 404);

        $survey = $response->survey;
        $survey->setAttribute('completed', $response->status === 'completed');
        $survey->setAttribute('response_id', $response->id);
        return response()->json(['data' => $survey]);
    }

    public function unreadNotification(Request $request)
    {
        $student = $this->resolveAuthenticatedStudent($request);
        abort_unless($student, 403);

        return response()->json(['data' => SatisfactionNotification::query()
            ->where('candidate_id', $student->id)
            ->whereNull('read_at')
            ->latest()
            ->first()]);
    }

    public function markNotificationRead(Request $request, SatisfactionNotification $notification)
    {
        $student = $this->resolveAuthenticatedStudent($request);
        abort_unless($student && $notification->candidate_id === $student->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['data' => $notification->fresh()]);
    }

    public function submit(SatisfactionSubmitRequest $request, SatisfactionSurvey $survey)
    {
        $student = $this->resolveAuthenticatedStudent($request); abort_unless($student, 403);
        $questions = $survey->questions()->where('is_active', true)->get()->keyBy('id');
        $answers = $request->validated('answers'); $seen = [];
        foreach ($answers as $a) {
            $q = $questions[$a['question_id']] ?? null;
            if (!$q || isset($seen[$a['question_id']])) throw ValidationException::withMessages(['answers'=>'Question invalide pour ce sondage.']);
            $seen[$a['question_id']] = true;
            if ($q->is_required && !isset($a['rating']) && !isset($a['selected_option']) && trim($a['answer_text'] ?? '') === '') throw ValidationException::withMessages(['answers'=>'Toutes les questions obligatoires doivent être renseignées.']);
            if ($q->type === 'rating' && (!isset($a['rating']) || $a['rating'] < 1 || $a['rating'] > 5)) throw ValidationException::withMessages(['answers'=>'Note invalide.']);
            if ($q->type === 'single_choice' && !in_array($a['selected_option'] ?? '', (array)$q->options, true)) throw ValidationException::withMessages(['answers'=>'Option invalide.']);
        }
        foreach ($questions as $q) if ($q->is_required && !isset($seen[$q->id])) throw ValidationException::withMessages(['answers'=>'Toutes les questions obligatoires doivent être renseignées.']);
        $response = DB::transaction(function () use ($survey,$student,$answers,$request) {
            // Lock the one durable response first, then inspect its state.
            // Filtering by `started` before the lock would leave a narrow
            // double-submit race between two browser tabs.
            $r = SatisfactionResponse::query()
                ->where('survey_id', $survey->id)
                ->where('candidate_id', $student->id)
                ->when($request->filled('response_id'), fn ($query) => $query->whereKey($request->input('response_id')))
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();
            if (! $r || $r->status !== 'started') {
                throw ValidationException::withMessages([
                    'survey' => 'Vous avez déjà répondu à cette enquête.',
                ]);
            }
            $google = collect($answers)->first(fn($a) => ($a['selected_option'] ?? null) !== null);
            $choice = match ($google['selected_option'] ?? null) { 'Oui'=>'yes', 'Plus tard'=>'later', 'Non'=>'no', default=>$request->input('google_choice') };
            $r->fill(['status'=>'completed','submitted_at'=>now(),'google_choice'=>$choice]); $r->save();
            foreach ($answers as $a) SatisfactionAnswer::updateOrCreate(['response_id'=>$r->id,'question_id'=>$a['question_id']],['rating'=>$a['rating']??null,'selected_option'=>$a['selected_option']??null,'answer_text'=>$a['answer_text']??null]); return $r;
        });
        SatisfactionNotification::query()
            ->where('candidate_id', $student->id)
            ->where('response_id', $response->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        // If other lifecycle conditions are already met, preserve every
        // independently eligible pending stage now that this one is complete.
        app(SatisfactionLifecycleService::class)->evaluateAll($student);
        $payload = $response->load('answers');
        $payload->google_review_url = $response->google_choice === 'yes' ? config('satisfaction.google_review_url') : null;
        return response()->json(['data'=>$payload], 201);
    }

    /** Prefer the bearer token's user when an old web session belongs to another account. */
    private function resolveAuthenticatedStudent(Request $request): ?Student
    {
        $user = $request->user();
        if ($request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            $tokenUser = $token?->tokenable;
            if ($tokenUser && $tokenUser->student) {
                $user = $tokenUser;
            }
        }
        if (!$user) {
            Log::warning('Satisfaction request without authenticated user');
            return null;
        }
        $student = $user->student ?: Student::where('user_id', $user->id)->first();
        Log::info('Satisfaction student resolved', [
            'auth_user_id' => $user->id,
            'student_id' => $student?->id,
            'student_status' => $student?->status,
        ]);
        return $student;
    }

    public function googleClick(Request $request, SatisfactionResponse $response)
    {
        $student = $this->resolveAuthenticatedStudent($request);
        abort_unless($student && $response->candidate_id === $student->id, 403);
        if ($response->status !== 'completed' || $response->survey?->stage !== 'after_training' || $response->google_choice !== 'yes') return response()->json(['message'=>'Google review is not available.'], 422);
        $response->update(['google_clicked'=>true,'google_clicked_at'=>now()]); return response()->json(['data'=>['google_review_url'=>config('satisfaction.google_review_url')]]);
    }

    public function adminList(Request $request)
    {
        $request->validate(['date_from'=>'nullable|date','date_to'=>'nullable|date','rating'=>'nullable|integer|min:1|max:5']);
        $q=SatisfactionResponse::with(['survey','candidate.user','answers.question','enrollment.trainings.reservation.monitor.user'])->latest('submitted_at');
        if($request->filled('survey_id'))$q->where('survey_id',$request->survey_id); if($request->filled('status'))$q->where('status',$request->status); if($request->filled('date_from'))$q->whereDate('submitted_at','>=',$request->date_from); if($request->filled('date_to'))$q->whereDate('submitted_at','<=',$request->date_to); if($request->filled('search'))$q->whereHas('candidate.user',fn($u)=>$u->where('name','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%')); if($request->filled('rating'))$q->whereHas('answers',fn($a)=>$a->where('rating',(int)$request->rating));
        return response()->json($q->paginate(min((int)$request->input('per_page',20),100)));
    }

    public function adminSurveys(){return response()->json(['data'=>SatisfactionSurvey::withCount('responses')->orderBy('stage')->get()]);}
    public function adminShow(SatisfactionResponse $response){return response()->json(['data'=>$response->load(['survey.questions','answers.question','candidate.user'])]);}
    public function statistics(){
        $ratings=SatisfactionAnswer::whereNotNull('rating'); $by=SatisfactionSurvey::get()->map(fn($s)=>['survey'=>$s->slug,'average'=>SatisfactionAnswer::whereHas('response',fn($r)=>$r->where('survey_id',$s->id)->where('status','completed'))->avg('rating')]);
        $questions=SatisfactionAnswer::select('question_id',DB::raw('COUNT(*) responses'),DB::raw('AVG(rating) average'),DB::raw('SUM(rating=1) one_star'),DB::raw('SUM(rating=2) two_star'),DB::raw('SUM(rating=3) three_star'),DB::raw('SUM(rating=4) four_star'),DB::raw('SUM(rating=5) five_star'))->whereNotNull('rating')->groupBy('question_id')->with('question.survey')->get();
        return response()->json(['data'=>['total_responses'=>SatisfactionResponse::where('status','completed')->count(),'overall_average'=>$ratings->avg('rating'),'total_rating_answers'=>$ratings->count(),'by_survey'=>$by,'per_question'=>$questions,'rating_distribution'=>$ratings->select('rating',DB::raw('count(*) total'))->groupBy('rating')->pluck('total','rating'),'google'=>['yes'=>SatisfactionResponse::where('google_choice','yes')->count(),'later'=>SatisfactionResponse::where('google_choice','later')->count(),'no'=>SatisfactionResponse::where('google_choice','no')->count(),'clicked'=>SatisfactionResponse::where('google_clicked',true)->count()]]]);
    }

    /** @return \Illuminate\Support\Collection<int, SatisfactionSurvey> */
    private function pendingSurveys(Student $student)
    {
        return SatisfactionResponse::query()
            ->with(['survey.questions' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->where('candidate_id', $student->id)
            ->where('status', 'started')
            ->latest()
            ->get()
            ->map(function (SatisfactionResponse $response) {
                $survey = $response->survey;
                $survey->setAttribute('completed', false);
                $survey->setAttribute('response_id', $response->id);
                $survey->setAttribute('offer_id', $response->offer_id);
                $survey->setAttribute('satisfaction_enrollment_id', $response->satisfaction_enrollment_id);
                return $survey;
            });
    }
}
