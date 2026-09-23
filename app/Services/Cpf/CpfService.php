<?php

namespace App\Services\Cpf;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletStatusEnum;
use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\Cpf\Cpf;
use App\Repository\V2\Admin\Cpf\StoreCpfRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\IncOrDecWalletRepo;

class CpfService implements CpfInterface
{
    /**
     * Create a new CPF entry.
     *
     * @param array $attributes
     * @return Cpf
     */
    public function create(array $attributes): Cpf
    {
        $attributes['user_id'] = auth()->user()->id;

        // Process wallet adjustment
        $this->processWalletAdjustment($attributes);

        // Create cart and cart details
        $cart = $this->createCart($attributes['student_id']);
        $this->createCartDetail($cart->id, $attributes['offer_id']);

        // Store CPF
        return $this->storeCpf($attributes);
    }

    /**
     * Process wallet adjustment for the student.
     *
     * @param array $attributes
     * @return void
     */
    protected function processWalletAdjustment(array $attributes): void
    {
        $hour = $this->getOfferBalance($attributes['offer_id']);
        IncOrDecWalletRepo::run(
            $attributes['student_id'],
            $attributes['offer_id'],
            WalletStatusEnum::INCREMENT->value,
            $hour
        );
    }

    /**
     * Get the balance for a specific offer.
     *
     * @param string $offerId
     * @return int
     */
    protected function getOfferBalance(string $offerId): int
    {
        return Offer::query()->findOrFail($offerId)->balance;
    }

    /**
     * Create a new cart for the student.
     *
     * @param string $studentId
     * @return Cart
     */
    protected function createCart(string $studentId): Cart
    {
        return Cart::query()->create([
            'student_id' => $studentId,
            'status' => CartStatusEnum::CPF->value,
        ]);
    }

    /**
     * Create cart details.
     *
     * @param string $cartId
     * @param string $offerId
     * @return void
     */
    protected function createCartDetail(string $cartId, string $offerId): void
    {
        CartDetail::query()->create([
            'offer_id' => $offerId,
            'quantity' => 1,
            'cart_id' => $cartId,
        ]);
    }

    /**
     * Store the CPF using the repository.
     *
     * @param array $attributes
     * @return Cpf
     */
    protected function storeCpf(array $attributes): Cpf
    {
        return StoreCpfRepo::run($attributes);
    }
}
