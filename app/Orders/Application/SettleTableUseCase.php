<?php

namespace App\Orders\Application;

use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;
use App\Orders\Domain\PaymentGatewayInterface;
use App\Orders\Domain\TableStatus;
use App\Orders\Domain\TaxCalculatorInterface;
use App\Orders\Infrastructure\Tax\TaxCountryConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettleTableUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PaymentGatewayInterface $paymentGateway,
        private TaxCalculatorInterface $taxCalculator
    ) {
    }

    /**
     * @return array{
     *   tableId: int,
     *   orderIds: list<int>,
     *   payment: array{provider: string, status: string, transactionId: string}
     * }
     */
    public function execute(int $tableId): array
    {
        return DB::transaction(function () use ($tableId): array {
            $active = array_values(array_filter(
                $this->orderRepository->findByTableId($tableId),
                fn ($order) => in_array($order->getStatus(), [OrderStatus::Open, OrderStatus::Sent], true)
            ));

            $subtotal = 0.0;
            $orderIds = [];
            foreach ($active as $order) {
                $orderIds[] = (int) $order->getId();
                $subtotal += $order->total();
            }

            $subtotal = round($subtotal, 2);
            $taxAmount = $this->taxCalculator->calculate($subtotal);
            $total = round($subtotal + $taxAmount, 2);
            $country = TaxCountryConfig::currentCountry();
            $currency = TaxCountryConfig::currency($country);

            $payment = $this->paymentGateway->charge($tableId, $total, $currency, $country);

            if (!$payment->isApproved()) {
                throw new \DomainException('Pago rechazado por la pasarela');
            }

            // ponytail: free even with no open orders — unbricks inconsistent billRequested/occupied states
            foreach ($active as $order) {
                $order->changeStatus(OrderStatus::Paid);
                $this->orderRepository->save($order);
            }

            DB::table('tables')->where('id', $tableId)->update([
                'status' => TableStatus::Free->value,
                'updated_at' => now(),
            ]);

            Log::info('table.settled', [
                'tableId' => $tableId,
                'transactionId' => $payment->transactionId,
                'amount' => $total,
                'currency' => $currency,
            ]);

            return [
                'tableId' => $tableId,
                'orderIds' => $orderIds,
                'payment' => $payment->toArray(),
            ];
        });
    }
}
