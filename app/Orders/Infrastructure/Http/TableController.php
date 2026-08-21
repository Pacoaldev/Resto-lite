<?php

namespace App\Orders\Infrastructure\Http;

use App\Orders\Application\RequestBillUseCase;
use App\Orders\Application\SettleTableUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    public function __construct(
        private RequestBillUseCase $requestBillUseCase,
        private SettleTableUseCase $settleTableUseCase
    ) {
    }

    public function index(): JsonResponse
    {
        $tables = DB::table('tables')->get();
        return response()->json($tables);
    }

    public function requestBill(int $id): JsonResponse
    {
        try {
            $bill = $this->requestBillUseCase->execute($id);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($bill);
    }

    public function settle(int $id): JsonResponse
    {
        try {
            $this->settleTableUseCase->execute($id);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(status: 204);
    }
}
