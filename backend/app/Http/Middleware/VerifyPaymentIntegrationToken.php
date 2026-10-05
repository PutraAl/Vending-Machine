<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaymentIntegrationToken
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $providedToken = $request->header(
            'X-Payment-Integration-Token'
        );

        $expectedToken = config(
            'services.payment_integration.token'
        );

        if (
            ! $providedToken
            || ! $expectedToken
            || ! hash_equals(
                $expectedToken,
                $providedToken
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment integration credentials.',
            ], 401);
        }

        return $next($request);
    }
}