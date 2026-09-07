<?php

namespace App\Orders\Application;

use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;
use App\Orders\Domain\TableStatus;
use App\Orders\Domain\TaxCalculatorInterface;
use App\Orders\Infrastructure\Tax\TaxCountryConfig;
use Illuminate\Support\Facades\DB;

class RequestBillUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private TaxCalculatorInterface $taxCalculator
    ) {
    }

    /**
     * @return array{
     *   tableId: int,
     *   orderIds: list<int>,
     *   items: list<array{name: string, price: float|int|string, quantity: int}>,
     *   subtotal: float,
     *   taxLabel: string,
     *   taxRate: float,
     *   taxAmount: float,
     *   total: float,
     *   currency: string,
     *   country: string
     * }
     */
    public function execute(int $tableId): array
    {
        return DB::transaction(function () use ($tableId): array {
            $table = DB::table('tables')
                ->where('id', $tableId)
                ->lockForUpdate()
                ->first();

            if (!$table) {
                throw new \DomainException('Mesa no encontrada');
            }

            // ponytail: si la mesa ya esta en billRequested, la cuenta ya fue generada → 422 idempotente
            if ($table->status === TableStatus::BillRequested->value) {
                throw new \DomainException('La cuenta ya fue solicitada para esta mesa');
            }

            $active = array_values(array_filter(
                $this->orderRepository->findByTableId($tableId),
                fn ($order) => in_array($order->getStatus(), [OrderStatus::Open, OrderStatus::Sent], true)
            ));

            if ($active === []) {
                throw new \DomainException('No hay pedidos abiertos para generar la cuenta');
            }

            $items = [];
            $subtotal = 0.0;
            $orderIds = [];

            foreach ($active as $order) {
                $orderIds[] = (int) $order->getId();
                $subtotal += $order->total();
                foreach ($order->getItems() as $item) {
                    $items[] = $item;
                }
            }

            $subtotal = round($subtotal, 2);
            $taxAmount = $this->taxCalculator->calculate($subtotal);
            $country = TaxCountryConfig::currentCountry();

            DB::table('tables')->where('id', $tableId)->update([
                'status' => TableStatus::BillRequested->value,
                'updated_at' => now(),
            ]);

            return [
                'tableId' => $tableId,
                'orderIds' => $orderIds,
                'items' => $items,
                'subtotal' => $subtotal,
                'taxLabel' => $this->taxCalculator->getLabel(),
                'taxRate' => $this->taxCalculator->getRate(),
                'taxAmount' => $taxAmount,
                'total' => round($subtotal + $taxAmount, 2),
                'currency' => TaxCountryConfig::currency($country),
                'country' => $country,
            ];
        });
    }
}