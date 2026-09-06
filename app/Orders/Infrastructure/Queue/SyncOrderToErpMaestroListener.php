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
        /** @var string $endpoint */
        $endpoint = config('services.erpMaestro.url') ?? '';

        // ponytail: sin URL configurada es un entorno sin ERP — no tiene sentido reintentar 5 veces
        if ($endpoint === '') {
            Log::info('ERP maestro no configurado, pedido queda solo en resto-lite', [
                'orderId' => $event->orderId,
                'tableId' => $event->tableId,
            ]);
            return;
        }

        Log::info('Sincronizando pedido con ERP maestro', [
            'orderId' => $event->orderId,
            'tableId' => $event->tableId,
        ]);

        // ponytail: solo errores de red se re-lanzan para que la cola reintente; errores 4xx/5xx son del ERP y deben surface
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