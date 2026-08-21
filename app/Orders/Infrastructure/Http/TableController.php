<?php

namespace App\Orders\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    public function index(): JsonResponse
    {
        $tables = DB::table('tables')->get();
        return response()->json($tables);
    }
}
