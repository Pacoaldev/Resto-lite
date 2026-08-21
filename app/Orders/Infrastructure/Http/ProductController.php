<?php

namespace App\Orders\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = DB::table('products')->get();
        return response()->json($products);
    }
}
