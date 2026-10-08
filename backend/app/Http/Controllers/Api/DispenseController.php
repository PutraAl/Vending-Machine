<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispense;
use App\Services\DispenseService;
use Illuminate\Http\JsonResponse;

class DispenseController extends Controller
{
    public function __construct(
        private readonly DispenseService $dispenseService
    ) {
    }

    public function complete(Dispense $dispense): JsonResponse
    {
        $dispense = $this->dispenseService
            ->completeDispense($dispense);

        return response()->json([
            'success' => true,
            'message' => 'Dispense completed successfully',
            'data' => $dispense,
        ]);
    }

    public function fail(Dispense $dispense): JsonResponse
    {
        $dispense = $this->dispenseService
            ->failDispense($dispense);

        return response()->json([
            'success' => true,
            'message' => 'Dispense failed and stock reservation released',
            'data' => $dispense,
        ]);
    }
}