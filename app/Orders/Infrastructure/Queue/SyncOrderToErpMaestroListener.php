<?php

namespace App\Orders\Infrastructure\Queue;

use App\Orders\Domain\OrderCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
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

        // ponytail: only network errors are swallowed so the queue can retry; business errors must surface
        try {
            Http::timeout(3)
                ->throw()
                ->post($endpoint . '/api/sync/orders', [
                    'externalOrderId' => $event->orderId,
                    'tableId' => $event->tableId,
                    'total' => $event->total,
                    'source' => 'resto-lite',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Conexión fallida con el ERP maestro, re-intento por cola: ' . $e->getMessage());
            throw $e;
        }
    }
}