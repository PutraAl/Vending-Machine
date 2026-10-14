<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispense;
use App\Services\DispenseReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DispenseReconciliationController extends Controller
{
    public function __construct(
        private readonly DispenseReconciliationService $service
    ) {}

    public function store(Request $request, Dispense $dispense): JsonResponse
    {
        $validated = $request->validate([
            'decision' => [
                'required',
                Rule::in([
                    'CONFIRMED_COMPLETED',
                    'CONFIRMED_FAILED',
                    'OUTCOME_UNKNOWN',
                ]),
            ],
            'reason' => ['required', 'string', 'max:5000'],
            'evidence' => ['nullable', 'string', 'max:10000'],
        ]);

        $record = $this->service->reconcile(
            $dispense,
            $request->user(),
            $validated['decision'],
            $validated['reason'],
            $validated['evidence'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Reconciliation recorded.',
            'data' => $record,
        ], 201);
    }
}