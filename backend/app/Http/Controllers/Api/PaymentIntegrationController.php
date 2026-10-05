<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class PaymentIntegrationController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function confirm(
        ConfirmPaymentRequest $request
    ): JsonResponse {
        $order = $this->orderService->confirmPayment(
            $request->string('order_code')->toString(),
            $request->string('payment_reference')->toString()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmation received',
            'data' => [
                'order_code' => $order->order_code,
                'status' => $order->status,
            ],
        ]);
    }
}