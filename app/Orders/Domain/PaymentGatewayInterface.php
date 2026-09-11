<?php

namespace App\Orders\Domain;

interface PaymentGatewayInterface
{
    public function charge(int $tableId, float $amount, string $currency, string $country): PaymentResult;
}
