<?php

namespace App\Orders\Infrastructure\Http;

use App\Orders\Application\RequestBillUseCase;
use App\Orders\Application\SettleTableUseCase;
use App\Orders\Application\ListTablesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TableController extends Controller
{
    public function __construct(
        private RequestBillUseCase $requestBillUseCase,
        private SettleTableUseCase $settleTableUseCase,
        private ListTablesUseCase $listTablesUseCase
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->listTablesUseCase->execute());
    }

    public function requestBill(int $id): JsonResponse
    {
        return response()->json($this->requestBillUseCase->execute($id));
    }

    public function settle(int $id): JsonResponse
    {
        return response()->json($this->settleTableUseCase->execute($id));
    }
}