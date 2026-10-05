<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DispenseService;
use Illuminate\Http\JsonResponse;

class DispenseController extends Controller
{
    public function __construct(
        private readonly DispenseService $dispenseService
    ) {
    }

    public function store(Order $order): JsonResponse
    {
        $dispense = $this->dispenseService
            ->startDispense($order);

        return response()->json([
            'success' => true,
            'message' => 'Dispense process started',
            'data' => $dispense,
        ], 201);
    }
}