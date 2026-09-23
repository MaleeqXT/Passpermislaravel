<?php

namespace Tests\Unit;

use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Repository\V2\Shared\Base\Billing\Strip\ChargeStripeClientRepo;
use App\Repository\V2\Shared\Base\Billing\Strip\RefundStripeClientRepo;
use App\Services\Payment\Strip\Payment\PaymentService;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_creates_separate_sales_for_each_cart_detail()
    {
        // create user + student
        $user = User::factory()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        // build a cart with two offers having distinct balances and prices
        $offer1 = Offer::factory()->create([ 'balance' => 10, 'final_price' => 100 ]);
        $offer2 = Offer::factory()->create([ 'balance' => 5,  'final_price' => 50  ]);
        $cart = Cart::factory()->create(['student_id' => $student->id]);
        CartDetail::factory()->create(["cart_id" => $cart->id, 'offer_id' => $offer1->id, 'tranches' => 0]);
        CartDetail::factory()->create(["cart_id" => $cart->id, 'offer_id' => $offer2->id, 'tranches' => 0]);

        // mock stripe client so no real request is sent
        $this->mock(ChargeStripeClientRepo::class, function ($mock) {
            $mock->shouldReceive('run')->andReturn((object)['client_secret' => 'secret']);
        });
        $this->mock(RefundStripeClientRepo::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $service = app(PaymentService::class);
        $attributes = ['amount' => 15, 'balance' => 15];
        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $service->action($attributes);
        $body = $response->getData(true);

        $this->assertArrayHasKey('sales', $body);
        $this->assertArrayHasKey('sale', $body); // legacy key should still be present
        $this->assertCount(2, $body['sales']);
        $this->assertEquals($body['sale']['id'], $body['sales'][0]['id']);

        // DB should have a row for each offer with correct balances and amounts
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseHas('sales', ['balance' => 10, 'amount' => 100]);
        $this->assertDatabaseHas('sales', ['balance' => 5, 'amount' => 50]);

        // call handleSuccessfulPayment to ensure wallets are stored correctly
        $firstSale = $body['sales'][0];
        $saleModel = \App\Models\Roles\Admin\Offer\Order\Sale::find($firstSale['id']);
        $service->handleSuccessfulPayment('pi_123', $saleModel);

        // wallets should be created for both offers with their own balances
        $this->assertDatabaseHas('wallets', ['offer_id' => $offer1->id, 'balance' => 10]);
        $this->assertDatabaseHas('wallets', ['offer_id' => $offer2->id, 'balance' => 5]);

        // student balance should equal sum of balances (not prices)
        $student->refresh();
        $this->assertEquals(15, $student->balance);

        // both sales in the database should now be marked paid with payment id
        $this->assertDatabaseHas('sales', ['id' => $body['sales'][0]['id'], 'payment_status' => SaleStatusEnum::PAID->value]);
        $this->assertDatabaseHas('sales', ['id' => $body['sales'][1]['id'], 'payment_status' => SaleStatusEnum::PAID->value]);
    }

    public function test_action_handles_partial_installment_and_other_offer()
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        // offer1 is split in 2 tranches; we will pay first installment only
        $offer1 = Offer::factory()->create([ 'balance' => 10, 'final_price' => 100, 'multi_payment' => 2 ]);
        $offer2 = Offer::factory()->create([ 'balance' => 5,  'final_price' => 50  ]);
        $cart = Cart::factory()->create(['student_id' => $student->id]);
        CartDetail::factory()->create(["cart_id" => $cart->id, 'offer_id' => $offer1->id, 'tranches' => 2, 'selected_installment_no' => 1]);
        CartDetail::factory()->create(["cart_id" => $cart->id, 'offer_id' => $offer2->id, 'tranches' => 0]);

        // mocks
        $this->mock(ChargeStripeClientRepo::class, function ($mock) {
            $mock->shouldReceive('run')->andReturn((object)['client_secret' => 'secret']);
        });
        $this->mock(RefundStripeClientRepo::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $service = app(PaymentService::class);
        // first installment 50/5 plus full second offer 50/5 -> totals 100/10
        $attributes = [
            'amount' => 100,
            'balance' => 10,
            'installments' => [
                ['offer_id' => $offer1->id, 'installment_no' => 1]
            ]
        ];
        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $service->action($attributes);
        $body = $response->getData(true);

        $this->assertArrayHasKey('sales', $body);
        $this->assertArrayHasKey('sale', $body);
        $this->assertCount(2, $body['sales']);
        $this->assertEquals($body['sale']['id'], $body['sales'][0]['id']);

        // check amounts/balances generated correctly
        $this->assertDatabaseHas('sales', ['balance' => 5, 'amount' => 50, 'installment_no' => 1]);
        $this->assertDatabaseHas('sales', ['balance' => 5, 'amount' => 50]);

        // handle payment
        $firstSale = $body['sales'][0];
        $saleModel = \App\Models\Roles\Admin\Offer\Order\Sale::find($firstSale['id']);
        $service->handleSuccessfulPayment('pi_123', $saleModel);

        $this->assertDatabaseHas('wallets', ['offer_id' => $offer1->id, 'balance' => 5]);
        $this->assertDatabaseHas('wallets', ['offer_id' => $offer2->id, 'balance' => 5]);
        $student->refresh();
        $this->assertEquals(10, $student->balance);
        $this->assertDatabaseHas('sales', ['id' => $body['sales'][0]['id'], 'payment_status' => SaleStatusEnum::PAID->value]);
        $this->assertDatabaseHas('sales', ['id' => $body['sales'][1]['id'], 'payment_status' => SaleStatusEnum::PAID->value]);
    }

    public function test_action_uses_agency_pricing_when_available()
    {
        $user = User::factory()->create(['ville' => 'Creil']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $offer = Offer::factory()->create([
            'balance' => 100,
            'final_price' => 1000,
            'agency_pricing' => json_encode([
                ['agency' => 'criel', 'original_price' => 800, 'balance' => 80],
                ['agency' => 'toulouse', 'original_price' => 900, 'balance' => 90],
            ]),
        ]);

        $cart = Cart::factory()->create(['student_id' => $student->id]);
        CartDetail::factory()->create(["cart_id" => $cart->id, 'offer_id' => $offer->id, 'tranches' => 0]);

        $this->mock(ChargeStripeClientRepo::class, function ($mock) {
            $mock->shouldReceive('run')->andReturn((object)['client_secret' => 'secret']);
        });
        $this->mock(RefundStripeClientRepo::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $service = app(PaymentService::class);
        // do not supply attributes - logic should compute pricing automatically
        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $service->action([]);
        $body = $response->getData(true);

        $this->assertCount(1, $body['sales']);
        $sale = $body['sales'][0];
        $this->assertEquals(800, $sale['amount']);
        $this->assertEquals(80, $sale['balance']);
    }

    public function test_action_uses_agency_pricing_for_each_line_in_multi_cart()
    {
        $user = User::factory()->create(['ville' => 'Creil']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $offer1 = Offer::factory()->create([
            'balance' => 50,
            'final_price' => 500,
            'agency_pricing' => json_encode([
                ['agency' => 'criel', 'original_price' => 500, 'balance' => 50],
            ]),
        ]);
        $offer2 = Offer::factory()->create([
            'balance' => 30,
            'final_price' => 300,
            'agency_pricing' => json_encode([
                ['agency' => 'criel', 'original_price' => 300, 'balance' => 30],
            ]),
        ]);

        $cart = Cart::factory()->create(['student_id' => $student->id]);
        CartDetail::factory()->create(["cart_id" => $cart->id, 'offer_id' => $offer1->id, 'tranches' => 0]);
        CartDetail::factory()->create(["cart_id" => $cart->id, 'offer_id' => $offer2->id, 'tranches' => 0]);

        $this->mock(ChargeStripeClientRepo::class, function ($mock) {
            $mock->shouldReceive('run')->andReturn((object)['client_secret' => 'secret']);
        });
        $this->mock(RefundStripeClientRepo::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $service = app(PaymentService::class);
        $response = $service->action([]);
        $body = $response->getData(true);

        $this->assertCount(2, $body['sales']);
        $this->assertDatabaseHas('sales', ['amount' => 500, 'balance' => 50]);
        $this->assertDatabaseHas('sales', ['amount' => 300, 'balance' => 30]);
    }
}
