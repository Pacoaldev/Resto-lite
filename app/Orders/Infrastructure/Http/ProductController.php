<?php

namespace App\Orders\Infrastructure\Http;

use App\Orders\Application\ListProductsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function __construct(
        private ListProductsUseCase $listProductsUseCase
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->listProductsUseCase->execute());
    }
}