<?php

namespace App\Orders\Domain;

final class PaymentResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $status,
        public readonly string $transactionId,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /** @return array{provider: string, status: string, transactionId: string} */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'status' => $this->status,
            'transactionId' => $this->transactionId,
        ];
    }
}
