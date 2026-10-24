<?php

namespace Tests\Unit;

use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Models\Roles\Student\User\Student;
use App\Services\Theory\TheoryCodeAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TheoryCodeAccessServiceTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousConnection = (string) config('database.default');
        config()->set('database.default', 'theory_code_access_test');
        config()->set('database.connections.theory_code_access_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        DB::purge('theory_code_access_test');
        DB::setDefaultConnection('theory_code_access_test');

        foreach ([
            'sales' => 'id TEXT PRIMARY KEY, student_id TEXT, cart_id TEXT, payment_status INTEGER, deleted_at TEXT',
            'carts' => 'id TEXT PRIMARY KEY, status INTEGER, deleted_at TEXT',
            'cart_details' => 'id TEXT PRIMARY KEY, cart_id TEXT, offer_id TEXT, deleted_at TEXT',
            'offers' => 'id TEXT PRIMARY KEY, type INTEGER, type_offre INTEGER, deleted_at TEXT',
        ] as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }
    }

    protected function tearDown(): void
    {
        DB::purge('theory_code_access_test');
        DB::setDefaultConnection($this->previousConnection);

        parent::tearDown();
    }

    public function test_confirmed_type_offre_code_online_purchase_grants_code_access(): void
    {
        $student = $this->purchase(
            paymentStatus: SaleStatusEnum::PAID->value,
            offerType: OffreTypeStatusEnum::CONDUITE->value,
            offerTypeOffre: OffreTypeEnum::CODE_ONLINE->value,
        );

        $this->assertTrue(app(TheoryCodeAccessService::class)->hasPaidPackageAccess($student));
    }

    public function test_pending_type_offre_code_online_purchase_does_not_grant_code_access(): void
    {
        $student = $this->purchase(
            paymentStatus: SaleStatusEnum::PENDING->value,
            offerType: OffreTypeStatusEnum::CONDUITE->value,
            offerTypeOffre: OffreTypeEnum::CODE_ONLINE->value,
        );

        $this->assertFalse(app(TheoryCodeAccessService::class)->hasPaidPackageAccess($student));
    }

    public function test_confirmed_non_code_purchase_does_not_grant_code_access(): void
    {
        $student = $this->purchase(
            paymentStatus: SaleStatusEnum::PAID->value,
            offerType: OffreTypeStatusEnum::CONDUITE->value,
            offerTypeOffre: OffreTypeEnum::FORFAIT->value,
        );

        $this->assertFalse(app(TheoryCodeAccessService::class)->hasPaidPackageAccess($student));
    }

    private function purchase(int $paymentStatus, int $offerType, int $offerTypeOffre): Student
    {
        $studentId = (string) Str::uuid();
        $cartId = (string) Str::uuid();
        $offerId = (string) Str::uuid();

        DB::table('carts')->insert([
            'id' => $cartId,
            'status' => CartStatusEnum::PAID->value,
        ]);
        DB::table('offers')->insert([
            'id' => $offerId,
            'type' => $offerType,
            'type_offre' => $offerTypeOffre,
        ]);
        DB::table('cart_details')->insert([
            'id' => (string) Str::uuid(),
            'cart_id' => $cartId,
            'offer_id' => $offerId,
        ]);
        DB::table('sales')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $studentId,
            'cart_id' => $cartId,
            'payment_status' => $paymentStatus,
        ]);

        return new Student(['id' => $studentId]);
    }
}
