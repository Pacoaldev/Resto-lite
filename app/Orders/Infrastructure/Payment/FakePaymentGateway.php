<?php

namespace App\Orders\Infrastructure\Payment;

use App\Orders\Domain\PaymentGatewayInterface;
use App\Orders\Domain\PaymentResult;

/**
 * Stub gateway for demo / PoC — always approves.
 * ponytail: swap for LoomisPay/Stripe adapter when a real TPV appears.
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    public function charge(int $tableId, float $amount, string $currency, string $country): PaymentResult
    {
        return new PaymentResult(
            provider: 'stub',
            status: 'approved',
            transactionId: 'pay_' . $tableId . '_' . str_replace('.', '', uniqid('', true)),
        );
    }
}
