<?php

namespace Tests\Unit;

use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Models\Roles\Student\User\Student;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use App\Services\SatisfactionLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'satisfaction_responses' => 'id TEXT PRIMARY KEY, survey_id TEXT, candidate_id TEXT, status TEXT, created_at TEXT, updated_at TEXT, UNIQUE(survey_id, candidate_id)',
            'satisfaction_notifications' => 'id TEXT PRIMARY KEY, candidate_id TEXT, survey_id TEXT, response_id TEXT, type TEXT, title TEXT, message TEXT, created_at TEXT, updated_at TEXT, UNIQUE(candidate_id, survey_id)',
            'review_monitors' => 'id TEXT PRIMARY KEY, reservation_id TEXT, monitor_id TEXT, is_absent INTEGER, comment TEXT',
            'reservations' => 'id TEXT PRIMARY KEY, date TEXT, end_at TEXT, hour REAL, is_active INTEGER, deleted_at TEXT',
            'trainings' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, offer_id TEXT, deleted_at TEXT',
            'offers' => 'id TEXT PRIMARY KEY, balance REAL, deleted_at TEXT',
            'cancellations' => 'id TEXT PRIMARY KEY, training_id TEXT',
            'media' => 'id TEXT PRIMARY KEY, mediable_id TEXT, mediable_type TEXT',
        ];
        foreach ($schemas as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }
        $this->student = new Student(['id' => (string) Str::uuid()]);
        $this->student->setRelation('user', new User(['email' => 'student@example.test']));
        SatisfactionSurvey::create(['stage' => 'before_training', 'is_active' => true]);
        SatisfactionSurvey::create(['stage' => 'during_training', 'is_active' => true]);
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
        DB::table('reservations')->insert(['id' => $reservationId, 'date' => '2020-01-01', 'end_at' => '12:00:00', 'hour' => 7, 'is_active' => 1]);
        DB::table('trainings')->insert(['id' => (string) Str::uuid(), 'student_id' => $this->student->id, 'reservation_id' => $reservationId, 'offer_id' => $offerId]);
        DB::table('review_monitors')->insert(['id' => (string) Str::uuid(), 'reservation_id' => $reservationId, 'is_absent' => 0, 'comment' => 'Lesson completed']);
        $response = app(SatisfactionLifecycleService::class)->evaluate($this->student);
        $this->assertSame('during_training', $response?->survey->stage);
        $this->assertSame(1, DB::table('satisfaction_notifications')->count());
        Notification::assertSentOnDemandTimes(FirstHourSatisfactionNotification::class, 1);
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
