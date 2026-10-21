<?php

namespace Tests\Unit;

use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Http\Controllers\SatisfactionController;
use App\Http\Requests\SatisfactionSubmitRequest;
use App\Models\Roles\Student\User\Student;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use App\Services\SatisfactionLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SatisfactionEvaluationPurchaseTest extends TestCase
{
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        // All test purchases and notifications stay in an isolated memory database.
        config()->set('database.default', 'satisfaction_evaluation_test');
        config()->set('database.connections.satisfaction_evaluation_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        DB::purge('satisfaction_evaluation_test');
        DB::setDefaultConnection('satisfaction_evaluation_test');
        Notification::fake();

        $schemas = [
            'sales' => 'id TEXT PRIMARY KEY, student_id TEXT, cart_id TEXT, payment_status INTEGER, payment_method INTEGER, payment_id TEXT, amount REAL, deleted_at TEXT',
            'carts' => 'id TEXT PRIMARY KEY, student_id TEXT, status INTEGER, deleted_at TEXT',
            'cart_details' => 'id TEXT PRIMARY KEY, cart_id TEXT, offer_id TEXT, deleted_at TEXT',
            'satisfaction_surveys' => 'id TEXT PRIMARY KEY, stage TEXT, is_active INTEGER, created_at TEXT, updated_at TEXT',
            'satisfaction_responses' => 'id TEXT PRIMARY KEY, survey_id TEXT, candidate_id TEXT, offer_id TEXT, status TEXT, google_choice TEXT, google_clicked INTEGER DEFAULT 0, google_clicked_at TEXT, submitted_at TEXT, created_at TEXT, updated_at TEXT, UNIQUE(survey_id, candidate_id)',
            'satisfaction_notifications' => 'id TEXT PRIMARY KEY, candidate_id TEXT, survey_id TEXT, response_id TEXT, type TEXT, title TEXT, message TEXT, read_at TEXT, email_attempts INTEGER DEFAULT 0, last_email_attempt_at TEXT, email_sent_at TEXT, created_at TEXT, updated_at TEXT, UNIQUE(candidate_id, survey_id)',
            'review_monitors' => 'id TEXT PRIMARY KEY, reservation_id TEXT, monitor_id TEXT, is_absent INTEGER, comment TEXT',
            'reservations' => 'id TEXT PRIMARY KEY, date TEXT, end_at TEXT, hour REAL, duration_minutes INTEGER, is_active INTEGER, deleted_at TEXT',
            'trainings' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, offer_id TEXT, deleted_at TEXT',
            'offers' => 'id TEXT PRIMARY KEY, balance REAL, deleted_at TEXT',
            'cancellations' => 'id TEXT PRIMARY KEY, training_id TEXT, status INTEGER',
            'student_exams' => 'id TEXT PRIMARY KEY, student_id TEXT, date_examen TEXT, status INTEGER, result_permis INTEGER',
            'satisfaction_questions' => 'id TEXT PRIMARY KEY, survey_id TEXT, section TEXT, question TEXT, type TEXT, options TEXT, is_required INTEGER, sort_order INTEGER, is_active INTEGER, created_at TEXT, updated_at TEXT',
            'satisfaction_answers' => 'id TEXT PRIMARY KEY, response_id TEXT, question_id TEXT, rating INTEGER, selected_option TEXT, answer_text TEXT, created_at TEXT, updated_at TEXT, UNIQUE(response_id, question_id)',
            'media' => 'id TEXT PRIMARY KEY, mediable_id TEXT, mediable_type TEXT',
        ];
        foreach ($schemas as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }
        $this->student = new Student(['id' => (string) Str::uuid()]);
        $user = new User(['id' => (string) Str::uuid(), 'email' => 'student@example.test']);
        $user->setRelation('student', $this->student);
        $this->student->setRelation('user', $user);
        SatisfactionSurvey::create(['stage' => 'before_training', 'is_active' => true]);
        SatisfactionSurvey::create(['stage' => 'during_training', 'is_active' => true]);
        SatisfactionSurvey::create(['stage' => 'after_training', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('satisfaction_evaluation_test');
        parent::tearDown();
    }

    public static function evaluationOffers(): array
    {
        return [
            'BM evaluation' => ['84aeacd3-a396-11f1-a172-9840bb4923f7', 38],
            'BA evaluation' => ['9eec8bb6-af81-4846-b729-5c646ead54f4', 45],
        ];
    }

    #[DataProvider('evaluationOffers')]
    public function test_only_confirmed_evaluation_purchase_creates_one_before_notification_and_email(string $offerId, int $amount): void
    {
        $saleId = $this->purchase($offerId, SaleStatusEnum::PENDING->value, null, $amount);
        $service = app(SatisfactionLifecycleService::class);
        $this->assertNull($service->evaluate($this->student));
        Notification::assertNothingSent();

        DB::table('sales')->where('id', $saleId)->update([
            'payment_status' => SaleStatusEnum::PAID->value, 'payment_id' => 'pi_confirmed_test',
        ]);
        $response = $service->evaluate($this->student);
        $this->assertSame('before_training', $response?->survey->stage);
        $this->assertSame($response->id, $service->evaluate($this->student)?->id);
        $this->assertSame(1, DB::table('satisfaction_notifications')->count());
        Notification::assertSentOnDemandTimes(FirstHourSatisfactionNotification::class, 1);

        $response->update(['status' => 'completed']);
        $this->purchase($offerId, amount: $amount);
        $this->assertNull($service->evaluate($this->student));
        $this->assertSame(1, SatisfactionResponse::count());
        Notification::assertSentOnDemandTimes(FirstHourSatisfactionNotification::class, 1);
    }

    public static function excludedPurchases(): array
    {
        return [
            'unrelated offer at 38 euros' => ['another-offer', 2, 'pi_test', 38, 1],
            'unrelated offer at 45 euros' => ['another-offer', 2, 'pi_test', 45, 1],
            'pending payment' => ['84aeacd3-a396-11f1-a172-9840bb4923f7', 1, 'pi_test', 38, 1],
            'refunded payment' => ['84aeacd3-a396-11f1-a172-9840bb4923f7', 3, 'pi_test', 38, 1],
            'cancelled payment' => ['84aeacd3-a396-11f1-a172-9840bb4923f7', 4, 'pi_test', 38, 1],
            'missing Stripe confirmation' => ['84aeacd3-a396-11f1-a172-9840bb4923f7', 2, null, 38, 1],
            'cash payment' => ['84aeacd3-a396-11f1-a172-9840bb4923f7', 2, 'cash_test', 38, 3],
        ];
    }

    #[DataProvider('excludedPurchases')]
    public function test_other_offers_and_unconfirmed_payments_do_not_trigger_before(string $offerId, int $status, ?string $paymentId, int $amount, int $method): void
    {
        $this->purchase($offerId, $status, $paymentId, $amount, $method);
        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));
        $this->assertSame(0, DB::table('satisfaction_notifications')->count());
        Notification::assertNothingSent();
    }

    public function test_a_different_students_evaluation_purchase_does_not_trigger_before(): void
    {
        $saleId = $this->purchase('84aeacd3-a396-11f1-a172-9840bb4923f7');
        DB::table('sales')->where('id', $saleId)->update(['student_id' => (string) Str::uuid()]);
        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));
        Notification::assertNothingSent();
    }

    public function test_students_without_evaluation_purchase_can_still_reach_during(): void
    {
        $offerId = (string) Str::uuid();
        $this->purchase($offerId);
        DB::table('offers')->insert(['id' => $offerId, 'balance' => 10]);
        $reservationId = (string) Str::uuid();
        DB::table('reservations')->insert(['id' => $reservationId, 'date' => '2020-01-01', 'end_at' => '12:00:00', 'hour' => 8, 'is_active' => 1]);
        DB::table('trainings')->insert(['id' => (string) Str::uuid(), 'student_id' => $this->student->id, 'reservation_id' => $reservationId, 'offer_id' => $offerId]);
        DB::table('review_monitors')->insert(['id' => (string) Str::uuid(), 'reservation_id' => $reservationId, 'is_absent' => 0, 'comment' => 'Lesson completed']);
        $response = app(SatisfactionLifecycleService::class)->evaluate($this->student);
        $this->assertSame('during_training', $response?->survey->stage);
        $this->assertSame(1, DB::table('satisfaction_notifications')->count());
        Notification::assertSentOnDemandTimes(FirstHourSatisfactionNotification::class, 1);
    }

    public function test_during_survey_uses_the_75_percent_completed_hours_threshold(): void
    {
        $offerId = (string) Str::uuid();
        DB::table('offers')->insert(['id' => $offerId, 'balance' => 10]);
        $reservationId = $this->completedLesson($offerId, 420);

        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));

        DB::table('reservations')->where('id', $reservationId)->update(['duration_minutes' => 480, 'hour' => 8]);
        $this->assertSame('during_training', app(SatisfactionLifecycleService::class)->evaluate($this->student)?->survey->stage);
    }

    public function test_during_survey_uses_exact_minutes_and_mixed_lesson_lengths(): void
    {
        $offerId = (string) Str::uuid();
        DB::table('offers')->insert(['id' => $offerId, 'balance' => 20]);

        // 14h15 is below the 15h threshold; a final 45-minute lesson reaches
        // it exactly. This proves that 45-minute durations are not rounded.
        foreach ([90, 90, 90, 90, 90, 90, 90, 90, 90, 45] as $minutes) {
            $this->completedLesson($offerId, $minutes);
        }
        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));

        $this->completedLesson($offerId, 45);
        $response = app(SatisfactionLifecycleService::class)->evaluate($this->student);
        $this->assertSame('during_training', $response?->survey->stage);
        $this->assertSame(1, SatisfactionResponse::where('survey_id', $response->survey_id)->count());
    }

    public function test_absent_and_cancelled_lessons_do_not_count_toward_during_threshold(): void
    {
        $offerId = (string) Str::uuid();
        DB::table('offers')->insert(['id' => $offerId, 'balance' => 4]);

        $this->completedLesson($offerId, 45);
        $this->completedLesson($offerId, 90);
        $this->completedLesson($offerId, 120, comment: '   ');
        $this->completedLesson($offerId, 120, absent: true);
        $this->completedLesson($offerId, 120, cancelled: true);
        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));

        $this->completedLesson($offerId, 45);
        $this->assertSame('during_training', app(SatisfactionLifecycleService::class)->evaluate($this->student)?->survey->stage);
    }

    public function test_during_survey_is_not_duplicated_after_the_threshold_is_crossed_again(): void
    {
        $offerId = (string) Str::uuid();
        DB::table('offers')->insert(['id' => $offerId, 'balance' => 2]);
        $this->completedLesson($offerId, 90);

        $service = app(SatisfactionLifecycleService::class);
        $service->evaluateAll($this->student);
        $this->completedLesson($offerId, 120);
        $service->evaluateAll($this->student);

        $duringSurveyId = SatisfactionSurvey::where('stage', 'during_training')->value('id');
        $this->assertSame(1, SatisfactionResponse::where('survey_id', $duringSurveyId)->where('candidate_id', $this->student->id)->count());
        $this->assertSame(1, SatisfactionNotification::where('survey_id', $duringSurveyId)->where('candidate_id', $this->student->id)->count());
    }

    public function test_confirmed_exam_date_activates_after_training_once(): void
    {
        DB::table('student_exams')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'date_examen' => '2030-01-01',
            'status' => ExamenStatusEnum::PENDING->value,
            'result_permis' => null,
        ]);

        $response = app(SatisfactionLifecycleService::class)->evaluate($this->student);
        $this->assertSame('after_training', $response?->survey->stage);
        $this->assertSame(1, SatisfactionResponse::where('survey_id', $response->survey_id)->count());
        $this->assertSame($response->id, app(SatisfactionLifecycleService::class)->evaluate($this->student)?->id);
    }

    public function test_unconfirmed_cancelled_or_finalized_exam_records_do_not_activate_after_training(): void
    {
        foreach ([
            ['date_examen' => null, 'status' => ExamenStatusEnum::PENDING->value, 'result_permis' => null],
            ['date_examen' => '2030-01-01', 'status' => ExamenStatusEnum::SUCCESS->value, 'result_permis' => 1],
            ['date_examen' => '2030-01-01', 'status' => ExamenStatusEnum::FAILED->value, 'result_permis' => 2],
        ] as $exam) {
            DB::table('student_exams')->insert(['id' => (string) Str::uuid(), 'student_id' => $this->student->id] + $exam);
        }

        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));
        $this->assertSame(0, SatisfactionResponse::whereHas('survey', fn ($query) => $query->where('stage', 'after_training'))->count());
    }

    public function test_multiple_eligible_stages_are_available_together_and_completed_stage_stays_completed(): void
    {
        $this->purchase('84aeacd3-a396-11f1-a172-9840bb4923f7');
        $offerId = (string) Str::uuid();
        DB::table('offers')->insert(['id' => $offerId, 'balance' => 2]);
        $this->completedLesson($offerId, 90);

        $responses = app(SatisfactionLifecycleService::class)->evaluateAll($this->student);
        $this->assertSame(['before_training', 'during_training'], $responses->map(fn ($response) => $response->survey->stage)->all());

        $before = SatisfactionResponse::whereHas('survey', fn ($query) => $query->where('stage', 'before_training'))->firstOrFail();
        $before->update(['status' => 'completed', 'submitted_at' => now()]);
        app(SatisfactionLifecycleService::class)->evaluateAll($this->student);

        $this->assertSame(1, SatisfactionResponse::whereHas('survey', fn ($query) => $query->where('stage', 'before_training'))->count());
        $this->assertSame(1, SatisfactionResponse::whereHas('survey', fn ($query) => $query->where('stage', 'during_training'))->where('status', 'started')->count());
    }

    public function test_failed_email_attempt_is_recorded_then_retried_without_creating_a_duplicate_response(): void
    {
        $this->purchase('84aeacd3-a396-11f1-a172-9840bb4923f7');
        $service = new RetryableSatisfactionLifecycleService();
        $service->shouldFail = true;
        $service->evaluateAll($this->student);

        $notification = SatisfactionNotification::firstOrFail();
        $this->assertSame(1, $notification->email_attempts);
        $this->assertNull($notification->email_sent_at);
        $notification->update(['last_email_attempt_at' => now()->subMinutes(16)]);

        $service->shouldFail = false;
        $service->evaluateAll($this->student);
        $notification->refresh();
        $this->assertSame(2, $notification->email_attempts);
        $this->assertNotNull($notification->email_sent_at);
        $this->assertSame(1, SatisfactionResponse::count());

        SatisfactionResponse::firstOrFail()->update(['status' => 'completed']);
        $service->evaluateAll($this->student);
        $this->assertSame(2, SatisfactionNotification::firstOrFail()->email_attempts);
    }

    public function test_direct_stage_access_is_scoped_to_the_authenticated_student_and_completed_state_is_visible(): void
    {
        $survey = SatisfactionSurvey::where('stage', 'before_training')->firstOrFail();
        $response = SatisfactionResponse::create(['survey_id' => $survey->id, 'candidate_id' => $this->student->id, 'status' => 'started']);
        $controller = app(SatisfactionController::class);

        $json = $controller->stage($this->studentRequest(), 'before_training');
        $this->assertFalse($json->getData(true)['data']['completed']);

        $response->update(['status' => 'completed']);
        $json = $controller->stage($this->studentRequest(), 'before_training');
        $this->assertTrue($json->getData(true)['data']['completed']);

        $otherStudent = new Student(['id' => (string) Str::uuid()]);
        $otherUser = new User(['id' => (string) Str::uuid(), 'email' => 'other@example.test']);
        $otherUser->setRelation('student', $otherStudent);
        $otherStudent->setRelation('user', $otherUser);

        try {
            $controller->stage($this->studentRequest($otherUser), 'before_training');
            $this->fail('A second student must not access another student response.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }

        try {
            $controller->stage($this->studentRequest(), 'not-a-stage');
            $this->fail('An invalid stage must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_completed_response_cannot_be_overwritten_by_a_second_submit(): void
    {
        $survey = SatisfactionSurvey::where('stage', 'before_training')->firstOrFail();
        $questionId = (string) Str::uuid();
        DB::table('satisfaction_questions')->insert([
            'id' => $questionId, 'survey_id' => $survey->id, 'question' => 'Votre note',
            'type' => 'rating', 'is_required' => 1, 'sort_order' => 1, 'is_active' => 1,
        ]);
        SatisfactionResponse::create(['survey_id' => $survey->id, 'candidate_id' => $this->student->id, 'status' => 'started']);
        $controller = app(SatisfactionController::class);

        $first = $controller->submit($this->submitRequest($questionId, 5), $survey);
        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(1, DB::table('satisfaction_answers')->count());
        $this->assertSame(5, DB::table('satisfaction_answers')->value('rating'));

        try {
            $controller->submit($this->submitRequest($questionId, 1), $survey);
            $this->fail('A completed survey must not accept another submission.');
        } catch (ValidationException $exception) {
            $this->assertSame('Vous avez déjà répondu à cette enquête.', $exception->errors()['survey'][0]);
        }

        $this->assertSame(1, DB::table('satisfaction_answers')->count());
        $this->assertSame(5, DB::table('satisfaction_answers')->value('rating'));
    }

    private function completedLesson(string $offerId, int $minutes, bool $absent = false, bool $cancelled = false, ?string $comment = 'Lesson completed'): string
    {
        $reservationId = (string) Str::uuid();
        $trainingId = (string) Str::uuid();
        DB::table('reservations')->insert([
            'id' => $reservationId,
            'date' => '2020-01-01',
            'end_at' => '12:00:00',
            'hour' => $minutes / 60,
            'duration_minutes' => $minutes,
            'is_active' => 1,
        ]);
        DB::table('trainings')->insert([
            'id' => $trainingId,
            'student_id' => $this->student->id,
            'reservation_id' => $reservationId,
            'offer_id' => $offerId,
        ]);
        DB::table('review_monitors')->insert([
            'id' => (string) Str::uuid(),
            'reservation_id' => $reservationId,
            'is_absent' => $absent ? 1 : 0,
            'comment' => $comment,
        ]);
        if ($cancelled) {
            DB::table('cancellations')->insert([
                'id' => (string) Str::uuid(),
                'training_id' => $trainingId,
                'status' => 3,
            ]);
        }

        return $reservationId;
    }

    private function studentRequest(?User $user = null): Request
    {
        $request = Request::create('/api/student/satisfaction/stage/before_training', 'GET');
        $request->setUserResolver(fn () => $user ?: $this->student->user);

        return $request;
    }

    private function submitRequest(string $questionId, int $rating): SatisfactionSubmitRequest
    {
        $request = SatisfactionSubmitRequest::create('/api/student/satisfaction/test/submit', 'POST', [
            'answers' => [['question_id' => $questionId, 'rating' => $rating]],
        ]);
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $request->setUserResolver(fn () => $this->student->user);
        $request->setValidator($this->app['validator']->make($request->all(), $request->rules()));

        return $request;
    }

    private function purchase(string $offerId, int $status = SaleStatusEnum::PAID->value, ?string $paymentId = 'pi_test', int $amount = 38, int $method = SalePaymentMethodEnum::STRIP->value): string
    {
        $cartId = (string) Str::uuid();
        $saleId = (string) Str::uuid();
        DB::table('carts')->insert(['id' => $cartId, 'student_id' => $this->student->id, 'status' => 2]);
        DB::table('cart_details')->insert(['id' => (string) Str::uuid(), 'cart_id' => $cartId, 'offer_id' => $offerId]);
        DB::table('sales')->insert([
            'id' => $saleId, 'student_id' => $this->student->id, 'cart_id' => $cartId,
            'payment_status' => $status, 'payment_method' => $method, 'payment_id' => $paymentId, 'amount' => $amount,
        ]);
        return $saleId;
    }
}

class RetryableSatisfactionLifecycleService extends SatisfactionLifecycleService
{
    public bool $shouldFail = false;

    protected function sendSurveyEmail(Student $student, string $stage): void
    {
        if ($this->shouldFail) {
            throw new \RuntimeException('Simulated transport failure');
        }
    }
}
