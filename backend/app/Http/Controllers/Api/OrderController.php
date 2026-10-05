<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with([
                'machine',
                'items.product',
                'items.slot',
            ])
            ->when(
                $request->filled('status'),
                function ($query) use ($request) {
                    $query->where(
                        'status',
                        strtoupper($request->string('status'))
                    );
                }
            )
            ->when(
                $request->filled('machine_id'),
                function ($query) use ($request) {
                    $query->where(
                        'machine_id',
                        $request->integer('machine_id')
                    );
                }
            )
            ->latest()
            ->paginate(
                min(
                    max(
                        $request->integer('per_page', 10),
                        1
                    ),
                    100
                )
            );

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $orders,
        ]);
    }

    public function store(
        CreateOrderRequest $request
    ): JsonResponse {
        $order = $this->orderService->createOrder(
            $request->integer('machine_id'),
            $request->validated('items')
        );

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'machine',
            'items.product',
            'items.slot',
            'dispenses',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order,
        ]);
    }
}