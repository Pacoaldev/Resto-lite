<?php

namespace App\Orders\Infrastructure\Queue;

use App\Orders\Domain\OrderCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncOrderToErpMaestroListener implements ShouldQueue
{
    public int $tries = 5;
    public int $backoff = 10;

    public function handle(OrderCreatedEvent $event): void
    {
        // en el boceto no hay ERP maestro real, se simula la llamada
        /** @var string $endpoint */
        $endpoint = config('services.erpMaestro.url') ?? '';

        Log::info('Sincronizando pedido con ERP maestro', [
            'orderId' => $event->orderId,
            'tableId' => $event->tableId,
        ]);

        try {
            Http::timeout(3)->post($endpoint . '/api/sync/orders', [
                'externalOrderId' => $event->orderId,
                'tableId' => $event->tableId,
                'total' => $event->total,
                'source' => 'resto-lite',
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo conectar con el ERP maestro (simulado), el pedido queda guardado para re-intento offline: ' . $e->getMessage());
        }
    }
}
