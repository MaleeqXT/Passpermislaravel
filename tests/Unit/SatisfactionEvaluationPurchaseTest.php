<?php

namespace Tests\Unit;

use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Http\Controllers\SatisfactionController;
use App\Http\Requests\SatisfactionSubmitRequest;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\Exam\StudentExam;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use App\Services\SatisfactionLifecycleService;
use App\Services\SatisfactionOfferTriggerService;
use App\Repository\V2\Shared\Schedule\Sale\EditSaleRepo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
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
        config()->set('satisfaction.during_training_threshold_percent', 75);

        $schemas = [
            'sales' => 'id TEXT PRIMARY KEY, student_id TEXT, cart_id TEXT, payment_status INTEGER, payment_method INTEGER, payment_id TEXT, amount REAL, balance INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'students' => 'id TEXT PRIMARY KEY, user_id TEXT, deleted_at TEXT',
            'carts' => 'id TEXT PRIMARY KEY, student_id TEXT, status INTEGER, deleted_at TEXT',
            'cart_details' => 'id TEXT PRIMARY KEY, cart_id TEXT, offer_id TEXT, balance REAL, quantity INTEGER, deleted_at TEXT',
            'satisfaction_enrollments' => 'id TEXT PRIMARY KEY, student_id TEXT, sale_id TEXT, offer_id TEXT, purchased_minutes INTEGER, is_first_hour INTEGER, is_driving_package INTEGER, created_at TEXT, updated_at TEXT, UNIQUE(sale_id, offer_id)',
            'satisfaction_surveys' => 'id TEXT PRIMARY KEY, stage TEXT, is_active INTEGER, created_at TEXT, updated_at TEXT',
            'satisfaction_responses' => 'id TEXT PRIMARY KEY, survey_id TEXT, candidate_id TEXT, offer_id TEXT, satisfaction_enrollment_id TEXT, status TEXT, google_choice TEXT, google_clicked INTEGER DEFAULT 0, google_clicked_at TEXT, submitted_at TEXT, created_at TEXT, updated_at TEXT, UNIQUE(survey_id, candidate_id, satisfaction_enrollment_id)',
            'satisfaction_notifications' => 'id TEXT PRIMARY KEY, candidate_id TEXT, survey_id TEXT, offer_id TEXT, satisfaction_enrollment_id TEXT, response_id TEXT, type TEXT, title TEXT, message TEXT, read_at TEXT, email_attempts INTEGER DEFAULT 0, last_email_attempt_at TEXT, email_sent_at TEXT, created_at TEXT, updated_at TEXT, UNIQUE(candidate_id, survey_id, satisfaction_enrollment_id)',
            'review_monitors' => 'id TEXT PRIMARY KEY, reservation_id TEXT, monitor_id TEXT, is_absent INTEGER, comment TEXT',
            'reservations' => 'id TEXT PRIMARY KEY, date TEXT, end_at TEXT, hour REAL, duration_minutes INTEGER, is_active INTEGER, deleted_at TEXT',
            'trainings' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, offer_id TEXT, satisfaction_enrollment_id TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'offers' => 'id TEXT PRIMARY KEY, balance REAL, type INTEGER, type_offre INTEGER, is_auto INTEGER DEFAULT 0, is_evaluation INTEGER DEFAULT 0, deleted_at TEXT',
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
        DB::table('students')->insert(['id' => $this->student->id, 'user_id' => $user->id]);
        SatisfactionSurvey::create(['stage' => 'before_training', 'is_active' => true]);
        SatisfactionSurvey::create(['stage' => 'during_training', 'is_active' => true]);
        SatisfactionSurvey::create(['stage' => 'after_training', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('satisfaction_evaluation_test');
        parent::tearDown();
    }

    public static function firstHourOffers(): array
    {
        return [
            'BM evaluation' => ['84aeacd3-a396-11f1-a172-9840bb4923f7', 38],
            'BA evaluation' => ['9eec8bb6-af81-4846-b729-5c646ead54f4', 45],
        ];
    }

    #[DataProvider('firstHourOffers')]
    public function test_confirmed_first_hour_purchase_creates_one_before_notification_and_email(string $offerId, int $amount): void
    {
        // These exact legacy €38/€45 offer identifiers remain supported even
        // when their historical metadata does not carry is_evaluation yet.
        $this->createOffer($offerId, 1, type: OffreTypeStatusEnum::CODE->value);
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
        $this->assertSame('before_training', $service->evaluate($this->student)?->survey->stage);
        $this->assertSame(2, SatisfactionResponse::count());
        Notification::assertSentOnDemandTimes(FirstHourSatisfactionNotification::class, 2);
    }

    public function test_confirmed_unrelated_driving_package_does_not_trigger_before(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 20);
        $this->purchase($offerId, SaleStatusEnum::PAID->value, null, 499, SalePaymentMethodEnum::CASH->value);

        $response = app(SatisfactionLifecycleService::class)->evaluate($this->student);

        $this->assertNull($response);
        $this->assertSame(0, SatisfactionNotification::count());
        Notification::assertNothingSent();
    }

    public function test_a_price_of_38_without_an_eligible_offer_id_does_not_trigger_before(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 1, type: OffreTypeStatusEnum::CODE->value);
        $this->purchase($offerId, SaleStatusEnum::PAID->value, 'pi_unrelated_38', 38);

        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));
        $this->assertSame(0, SatisfactionResponse::whereHas('survey', fn ($query) => $query->where('stage', 'before_training'))->count());
    }

    public function test_paid_status_transition_reconciles_the_student_after_commit(): void
    {
        $offerId = '84aeacd3-a396-11f1-a172-9840bb4923f7';
        $this->createOffer($offerId, 1, type: OffreTypeStatusEnum::CODE->value);
        $saleId = $this->purchase($offerId, SaleStatusEnum::PENDING->value, null, 38);
        $lifecycle = new RecordingSatisfactionLifecycleService();
        $this->app->instance(SatisfactionLifecycleService::class, $lifecycle);

        EditSaleRepo::run(Sale::query()->findOrFail($saleId), [
            'payment_status' => SaleStatusEnum::PAID->value,
            'payment_id' => 'pi_confirmed_test',
        ]);

        $this->assertSame([$this->student->id], $lifecycle->reconciledStudentIds);
    }

    public function test_paid_first_hour_remains_eligible_after_its_cart_or_offer_is_archived(): void
    {
        $offerId = '9eec8bb6-af81-4846-b729-5c646ead54f4';
        $this->createOffer($offerId, 1, type: OffreTypeStatusEnum::CODE->value);
        $saleId = $this->purchase($offerId, SaleStatusEnum::PAID->value, 'pi_confirmed_test', 45);
        $cartId = DB::table('sales')->where('id', $saleId)->value('cart_id');

        DB::table('carts')->where('id', $cartId)->update(['deleted_at' => now()]);
        DB::table('cart_details')->where('cart_id', $cartId)->update(['deleted_at' => now()]);
        DB::table('offers')->where('id', $offerId)->update(['deleted_at' => now()]);

        $response = app(SatisfactionLifecycleService::class)->evaluate($this->student);

        $this->assertSame('before_training', $response?->survey->stage);
        Notification::assertSentOnDemandTimes(FirstHourSatisfactionNotification::class, 1);
    }

    public function test_code_products_and_unconfirmed_driving_purchases_do_not_trigger_before(): void
    {
        $codeOfferId = (string) Str::uuid();
        $this->createOffer($codeOfferId, 20, type: OffreTypeStatusEnum::CODE->value);
        $this->purchase($codeOfferId, SaleStatusEnum::PAID->value, 'pi_code', 38);

        $drivingOfferId = (string) Str::uuid();
        $this->createOffer($drivingOfferId, 20);
        $this->purchase($drivingOfferId, SaleStatusEnum::PENDING->value, null, 499);

        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));
        $this->assertSame(0, DB::table('satisfaction_notifications')->count());
        Notification::assertNothingSent();
    }

    public function test_a_different_students_evaluation_purchase_does_not_trigger_before(): void
    {
        $offerId = '84aeacd3-a396-11f1-a172-9840bb4923f7';
        $this->createOffer($offerId, 1, isEvaluation: true);
        $saleId = $this->purchase($offerId);
        DB::table('sales')->where('id', $saleId)->update(['student_id' => (string) Str::uuid()]);
        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));
        Notification::assertNothingSent();
    }

    public function test_students_without_evaluation_purchase_can_still_reach_during(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 10);
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
        $this->createOffer($offerId, 10);
        $reservationId = $this->completedLesson($offerId, 420);

        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));

        DB::table('reservations')->where('id', $reservationId)->update(['duration_minutes' => 480, 'hour' => 8]);
        $this->assertSame('during_training', app(SatisfactionLifecycleService::class)->evaluate($this->student)?->survey->stage);
    }

    public function test_during_survey_uses_exact_minutes_and_mixed_lesson_lengths(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 20);

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

    public function test_twenty_hour_package_activates_during_at_fifteen_hours_once(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 20);
        $lifecycle = app(SatisfactionLifecycleService::class);

        $this->assertNull($lifecycle->evaluate($this->student));
        $this->completedLesson($offerId, 600); // 10 hours
        $this->assertNull($lifecycle->evaluate($this->student));
        $this->completedLesson($offerId, 240); // 14 hours
        $this->assertNull($lifecycle->evaluate($this->student));
        $this->completedLesson($offerId, 60); // 15 hours exactly
        $this->assertSame('during_training', $lifecycle->evaluate($this->student)?->survey->stage);
        $this->completedLesson($offerId, 60); // 16+ hours
        $lifecycle->evaluateAll($this->student);

        $duringSurveyId = SatisfactionSurvey::where('stage', 'during_training')->value('id');
        $this->assertSame(1, SatisfactionResponse::where('survey_id', $duringSurveyId)->where('candidate_id', $this->student->id)->count());
        $this->assertSame(1, SatisfactionNotification::where('survey_id', $duringSurveyId)->where('candidate_id', $this->student->id)->count());
    }

    public static function drivingPackageTypes(): array
    {
        return [
            'manual package' => [false],
            'automatic package' => [true],
        ];
    }

    #[DataProvider('drivingPackageTypes')]
    public function test_completed_monitor_review_activates_during_without_a_student_refresh_for_manual_and_automatic_packages(bool $isAuto): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 10, isAuto: $isAuto);
        $this->completedLesson($offerId, 450); // Exactly 7.5 hours (75% of 10)

        $review = ReviewMonitor::query()->firstOrFail();
        $this->assertTrue(app(SatisfactionOfferTriggerService::class)->processCompletedLesson($review));

        $duringSurveyId = SatisfactionSurvey::where('stage', 'during_training')->value('id');
        $this->assertSame(1, SatisfactionResponse::where('survey_id', $duringSurveyId)->where('candidate_id', $this->student->id)->where('status', 'started')->count());
        $this->assertSame(1, SatisfactionNotification::where('survey_id', $duringSurveyId)->where('candidate_id', $this->student->id)->count());
    }

    public function test_during_threshold_is_configurable_without_losing_exact_minute_calculation(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 20);

        // 14h is below the default 75% threshold (15h), but exactly reaches
        // a configured 70% threshold for this package.
        $this->completedLesson($offerId, 840);
        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));

        config()->set('satisfaction.during_training_threshold_percent', 70);
        $this->assertSame('during_training', app(SatisfactionLifecycleService::class)->evaluate($this->student)?->survey->stage);
    }

    public function test_future_booked_lessons_do_not_count_toward_during_threshold(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 10);
        $reservationId = $this->completedLesson($offerId, 480);
        DB::table('reservations')->where('id', $reservationId)->update([
            'date' => now()->addDay()->toDateString(),
        ]);

        $this->assertNull(app(SatisfactionLifecycleService::class)->evaluate($this->student));
    }

    public function test_absent_and_cancelled_lessons_do_not_count_toward_during_threshold(): void
    {
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 4);

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
        $this->createOffer($offerId, 2);
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
        $this->createOffer((string) Str::uuid(), 20);
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

    public function test_exam_rescheduling_never_creates_a_second_after_survey_or_notification(): void
    {
        $this->createOffer((string) Str::uuid(), 20);
        $examId = (string) Str::uuid();
        DB::table('student_exams')->insert([
            'id' => $examId,
            'student_id' => $this->student->id,
            'date_examen' => '2030-01-01',
            'status' => ExamenStatusEnum::PENDING->value,
            'result_permis' => null,
        ]);

        app(SatisfactionLifecycleService::class)->evaluateAll($this->student);
        $afterSurveyId = SatisfactionSurvey::where('stage', 'after_training')->value('id');
        SatisfactionResponse::where('survey_id', $afterSurveyId)
            ->where('candidate_id', $this->student->id)
            ->update(['status' => 'completed', 'submitted_at' => now()]);

        DB::table('student_exams')->where('id', $examId)->update(['date_examen' => '2030-02-15']);
        app(SatisfactionLifecycleService::class)->evaluateAll($this->student);

        $this->assertSame(1, SatisfactionResponse::where('survey_id', $afterSurveyId)->where('candidate_id', $this->student->id)->count());
        $this->assertSame('completed', SatisfactionResponse::where('survey_id', $afterSurveyId)->where('candidate_id', $this->student->id)->value('status'));
        $this->assertSame(1, SatisfactionNotification::where('survey_id', $afterSurveyId)->where('candidate_id', $this->student->id)->count());
        Notification::assertSentOnDemandTimes(FirstHourSatisfactionNotification::class, 1);
    }

    public function test_saving_a_confirmed_official_exam_date_triggers_after_for_every_admin_workflow(): void
    {
        $this->createOffer((string) Str::uuid(), 20);

        StudentExam::withoutTimestamps(function (): void {
            StudentExam::query()->create([
                'student_id' => $this->student->id,
                'date_examen' => '2030-01-01',
                'status' => ExamenStatusEnum::PENDING->value,
                'result_permis' => null,
            ]);
        });

        $afterSurveyId = SatisfactionSurvey::where('stage', 'after_training')->value('id');
        $this->assertSame(1, SatisfactionResponse::where('survey_id', $afterSurveyId)->where('candidate_id', $this->student->id)->count());
        $this->assertSame(1, SatisfactionNotification::where('survey_id', $afterSurveyId)->where('candidate_id', $this->student->id)->count());

        StudentExam::withoutTimestamps(function () use ($afterSurveyId): void {
            $exam = StudentExam::query()->firstOrFail();
            $exam->date_examen = '2030-02-15';
            $exam->save();
            $this->assertSame(1, SatisfactionResponse::where('survey_id', $afterSurveyId)->where('candidate_id', $this->student->id)->count());
        });
    }

    public function test_unconfirmed_cancelled_or_finalized_exam_records_do_not_activate_after_training(): void
    {
        $this->createOffer((string) Str::uuid(), 20);
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
        $this->createOffer('84aeacd3-a396-11f1-a172-9840bb4923f7', 1, isEvaluation: true);
        $this->purchase('84aeacd3-a396-11f1-a172-9840bb4923f7');
        $offerId = (string) Str::uuid();
        $this->createOffer($offerId, 2);
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
        $this->createOffer('84aeacd3-a396-11f1-a172-9840bb4923f7', 1, isEvaluation: true);
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
        $startedResponse = SatisfactionResponse::create(['survey_id' => $survey->id, 'candidate_id' => $this->student->id, 'status' => 'started']);
        SatisfactionNotification::create([
            'candidate_id' => $this->student->id,
            'survey_id' => $survey->id,
            'response_id' => $startedResponse->id,
            'type' => 'satisfaction_before_training',
            'title' => 'Votre avis compte',
            'message' => 'Répondez à votre enquête.',
        ]);
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
        $this->assertNotNull(SatisfactionNotification::where('response_id', $first->getData(true)['data']['id'])->value('read_at'));
    }

    public function test_database_unique_constraint_prevents_duplicate_stage_responses(): void
    {
        $survey = SatisfactionSurvey::where('stage', 'before_training')->firstOrFail();
        $enrollmentId = (string) Str::uuid();
        DB::table('satisfaction_enrollments')->insert([
            'id' => $enrollmentId, 'student_id' => $this->student->id, 'sale_id' => (string) Str::uuid(),
            'offer_id' => (string) Str::uuid(), 'purchased_minutes' => 60, 'is_first_hour' => 1, 'is_driving_package' => 0,
        ]);
        SatisfactionResponse::create([
            'survey_id' => $survey->id,
            'candidate_id' => $this->student->id,
            'satisfaction_enrollment_id' => $enrollmentId,
            'status' => 'started',
        ]);

        try {
            SatisfactionResponse::create([
                'survey_id' => $survey->id,
                'candidate_id' => $this->student->id,
                'satisfaction_enrollment_id' => $enrollmentId,
                'status' => 'started',
            ]);
            $this->fail('The database must reject a duplicate enrolment/stage response.');
        } catch (QueryException) {
            $this->assertSame(1, SatisfactionResponse::where('survey_id', $survey->id)->where('candidate_id', $this->student->id)->count());
        }
    }

    public function test_all_completed_stages_leave_no_pending_survey_and_never_reactivate(): void
    {
        $evaluationOfferId = '84aeacd3-a396-11f1-a172-9840bb4923f7';
        $this->createOffer($evaluationOfferId, 1, type: OffreTypeStatusEnum::CODE->value, isEvaluation: true);
        $this->purchase($evaluationOfferId);

        $trainingOfferId = (string) Str::uuid();
        $this->createOffer($trainingOfferId, 2);
        $this->completedLesson($trainingOfferId, 90);

        DB::table('student_exams')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'date_examen' => '2030-01-01',
            'status' => ExamenStatusEnum::PENDING->value,
            'result_permis' => null,
        ]);

        app(SatisfactionLifecycleService::class)->evaluateAll($this->student);
        $controller = app(SatisfactionController::class);

        foreach (SatisfactionSurvey::query()->orderBy('stage')->get() as $survey) {
            $questionId = (string) Str::uuid();
            DB::table('satisfaction_questions')->insert([
                'id' => $questionId,
                'survey_id' => $survey->id,
                'question' => 'Votre note',
                'type' => 'rating',
                'is_required' => 1,
                'sort_order' => 1,
                'is_active' => 1,
            ]);
            $response = $controller->submit($this->submitRequest($questionId, 5), $survey);
            $this->assertSame(201, $response->getStatusCode());
        }

        $pending = $controller->pending($this->studentRequest(), app(SatisfactionLifecycleService::class));
        $this->assertSame([], $pending->getData(true)['data']);
        $this->assertSame(3, SatisfactionResponse::where('candidate_id', $this->student->id)->where('status', 'completed')->count());
        $this->assertSame(0, SatisfactionNotification::where('candidate_id', $this->student->id)->whereNull('read_at')->count());

        app(SatisfactionLifecycleService::class)->evaluateAll($this->student);
        $this->assertSame(3, SatisfactionResponse::where('candidate_id', $this->student->id)->count());
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
        DB::table('cart_details')->insert(['id' => (string) Str::uuid(), 'cart_id' => $cartId, 'offer_id' => $offerId, 'balance' => null, 'quantity' => 1]);
        DB::table('sales')->insert([
            'id' => $saleId, 'student_id' => $this->student->id, 'cart_id' => $cartId,
            'payment_status' => $status, 'payment_method' => $method, 'payment_id' => $paymentId, 'amount' => $amount,
            // This is the immutable purchased package quantity, deliberately
            // separate from the student's mutable wallet balance.
            'balance' => DB::table('offers')->where('id', $offerId)->value('balance') ?? 0,
        ]);
        return $saleId;
    }

    private function createOffer(
        string $offerId,
        int $balance,
        int $type = OffreTypeStatusEnum::CONDUITE->value,
        int $typeOffre = OffreTypeEnum::FORFAIT->value,
        bool $isEvaluation = false,
        bool $isAuto = false,
    ): void {
        DB::table('offers')->insert([
            'id' => $offerId,
            'balance' => $balance,
            'type' => $type,
            'type_offre' => $typeOffre,
            'is_auto' => $isAuto ? 1 : 0,
            'is_evaluation' => $isEvaluation ? 1 : 0,
        ]);
        if ($type === OffreTypeStatusEnum::CONDUITE->value
            && $typeOffre === OffreTypeEnum::FORFAIT->value
            && !in_array($offerId, ['84aeacd3-a396-11f1-a172-9840bb4923f7', '9eec8bb6-af81-4846-b729-5c646ead54f4'], true)) {
            $this->purchase($offerId, SaleStatusEnum::PAID->value, 'pi_package_' . Str::uuid(), 0);
        }
    }
}

class RetryableSatisfactionLifecycleService extends SatisfactionLifecycleService
{
    public bool $shouldFail = false;

    protected function sendSurveyEmail(Student $student, string $stage, string $responseId): void
    {
        if ($this->shouldFail) {
            throw new \RuntimeException('Simulated transport failure');
        }
    }
}

class RecordingSatisfactionLifecycleService extends SatisfactionLifecycleService
{
    /** @var list<string> */
    public array $reconciledStudentIds = [];

    public function evaluateAll(Student $student): \Illuminate\Support\Collection
    {
        $this->reconciledStudentIds[] = $student->id;

        return collect();
    }
}
