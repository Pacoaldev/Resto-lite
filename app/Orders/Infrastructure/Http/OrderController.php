<?php

namespace App\Orders\Infrastructure\Http;

use App\Orders\Application\ChangeOrderStatusUseCase;
use App\Orders\Application\CreateOrderUseCase;
use App\Orders\Domain\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function __construct(
        private CreateOrderUseCase $createOrderUseCase,
        private ChangeOrderStatusUseCase $changeOrderStatusUseCase
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tableId' => 'required|integer|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = $this->createOrderUseCase->execute(
            tableId: $validated['tableId'],
            items: $validated['items']
        );

        return response()->json([
            'id' => $order->getId(),
            'tableId' => $order->getTableId(),
            'status' => $order->getStatus()->value,
            'items' => $order->getItems(),
            'total' => $order->total(),
        ], 201);
    }

    public function updateStatus(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,sent,paid,cancelled',
        ]);

        $this->changeOrderStatusUseCase->execute(
            orderId: $orderId,
            newStatus: OrderStatus::from($validated['status'])
        );

        return response()->json(status: 204);
    }
}
