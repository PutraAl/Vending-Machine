<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\DispenseService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly DispenseService $dispenseService
    ) {
    }

    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_code' => [
                'required',
                'string',
                'max:50',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $order = $this->orderService->confirmPayment(
            $validated['order_code'],
            $validated['idempotency_key']
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment simulated successfully',
            'data' => [
                'payment' => $order->payment,
                'order' => [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                ],
            ],
        ], 201);
    }

    public function confirmDispense(
        Payment $payment
    ): JsonResponse {
        $payment->load([
            'order',
        ]);

        $order = $payment->order;

        if ($order === null) {
            return response()->json([
                'success' => false,
                'message' => 'Payment order was not found.',
            ], 404);
        }

        if ($order->status !== 'READY_TO_DISPENSE') {
            throw ValidationException::withMessages([
                'payment' => [
                    "Payment cannot start dispense because the order is currently {$order->status}.",
                ],
            ]);
        }

        $dispense = $this->dispenseService->startDispense($order);

        return response()->json([
            'success' => true,
            'message' => 'Dispense process started',
            'data' => [
                'payment_id' => $payment->id,
                'order' => [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'status' => $dispense->order->status,
                ],
                'dispense' => $dispense,
            ],
        ], 201);
    }
}