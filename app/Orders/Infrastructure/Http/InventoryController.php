<?php

namespace App\Orders\Infrastructure\Http;

use App\Orders\Application\ListInventoryUseCase;
use App\Orders\Application\ListStockMovementsUseCase;
use App\Orders\Application\RegisterWasteUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InventoryController extends Controller
{
    public function __construct(
        private ListInventoryUseCase $listInventoryUseCase,
        private ListStockMovementsUseCase $listStockMovementsUseCase,
        private RegisterWasteUseCase $registerWasteUseCase
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->listInventoryUseCase->execute());
    }

    public function movements(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);

        return response()->json($this->listStockMovementsUseCase->execute($limit));
    }

    public function waste(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productId' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $result = $this->registerWasteUseCase->execute(
            (int) $validated['productId'],
            (int) $validated['quantity'],
            $validated['reason'] ?? null
        );

        return response()->json($result);
    }
}
