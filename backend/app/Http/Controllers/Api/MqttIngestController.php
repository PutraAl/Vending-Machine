<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispense;
use App\Models\Machine;
use App\Models\MachineError;
use App\Models\MachineSlot;
use App\Models\Telemetry;
use App\Services\DispenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MqttIngestController extends Controller
{
    public function __construct(
        private readonly DispenseService $dispenseService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // Authenticate EMQX HTTP request.
        $expectedToken = (string) config('mqtt.ingest_token', '');
        $providedToken = $request->header('X-MQTT-INGEST-TOKEN');

        if (
            $expectedToken === ''
            || ! is_string($providedToken)
            || ! hash_equals($expectedToken, $providedToken)
        ) {
            return response()->json([
                'message' => 'Unauthorized MQTT ingestion request.',
            ], 401);
        }

        $validated = Validator::make($request->all(), [
            'topic' => [
                'required',
                'string',
                'in:vending/machine/temperature,vending/machine/status,vending/machine/inventory,vending/machine/error',
            ],
            'payload' => ['required', 'array'],
        ])->validate();

        $topic = $validated['topic'];
        $payload = $validated['payload'];

        $machineId = filter_var(
            $payload['machine_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($machineId === false || $machineId < 1) {
            return response()->json([
                'message' => 'Invalid or missing machine_id.',
            ], 422);
        }

        $machine = Machine::find($machineId);

        if (! $machine) {
            return response()->json([
                'message' => 'Machine not found.',
            ], 404);
        }

        switch ($topic) {
            case 'vending/machine/temperature':
                $temperature = $payload['temperature'] ?? null;

                if (
                    ! is_numeric($temperature)
                    || ! is_finite((float) $temperature)
                ) {
                    return response()->json([
                        'message' => 'Invalid temperature.',
                    ], 422);
                }

                $latestState = Telemetry::query()
                    ->where('machine_id', $machineId)
                    ->latest('created_at')
                    ->value('state') ?? 'IDLE';

                Telemetry::create([
                    'machine_id' => $machineId,
                    'temperature' => $temperature,
                    'state' => $latestState,
                    'door_status' => null,
                    'created_at' => now(),
                ]);

                break;

            case 'vending/machine/status':
                $state = $payload['state'] ?? null;

                if (
                    ! is_string($state)
                    || trim($state) === ''
                ) {
                    return response()->json([
                        'message' => 'Invalid machine state.',
                    ], 422);
                }

                Telemetry::create([
                    'machine_id' => $machineId,
                    'temperature' => null,
                    'state' => strtoupper(trim($state)),
                    'door_status' => null,
                    'created_at' => now(),
                ]);

                // Complete dispense only after confirmed DONE.
                $dispenseId = $payload['dispense_id'] ?? null;

                if (
                    strtoupper(trim($state)) === 'DONE'
                    && is_numeric($dispenseId)
                    && (int) $dispenseId > 0
                ) {
                    $dispense = Dispense::query()
                        ->whereKey((int) $dispenseId)
                        ->where('machine_id', $machineId)
                        ->first();

                    if ($dispense) {
                        $this->dispenseService
                            ->completeDispense($dispense);
                    } else {
                        Log::warning(
                            'MQTT DONE received for unknown dispense.',
                            [
                                'machine_id' => $machineId,
                                'dispense_id' => $dispenseId,
                            ]
                        );
                    }
                }

                break;

            case 'vending/machine/inventory':
                $slotCode = $payload['slot'] ?? null;
                $currentQty = $payload['current_qty'] ?? null;

                if (
                    ! is_string($slotCode)
                    || trim($slotCode) === ''
                    || filter_var(
                        $currentQty,
                        FILTER_VALIDATE_INT
                    ) === false
                    || (int) $currentQty < 0
                ) {
                    return response()->json([
                        'message' => 'Invalid inventory payload.',
                    ], 422);
                }

                $slot = MachineSlot::query()
                    ->where('machine_id', $machineId)
                    ->where('slot_code', $slotCode)
                    ->first();

                if (! $slot) {
                    return response()->json([
                        'message' => 'Machine slot not found.',
                    ], 404);
                }

                if (
                    (int) $currentQty > $slot->capacity
                    || (int) $currentQty < $slot->hold_qty
                ) {
                    Log::warning(
                        'MQTT inventory reading is outside allowed bounds.',
                        [
                            'machine_id' => $machineId,
                            'slot' => $slotCode,
                            'reported_qty' => $currentQty,
                        ]
                    );
                }

                // Inventory telemetry must not overwrite business stock.
                break;

            case 'vending/machine/error':
                $rawErrorCode = $payload['error']
                    ?? $payload['error_code']
                    ?? null;

                $errorCode = is_string($rawErrorCode)
                    && trim($rawErrorCode) !== ''
                    ? strtoupper(trim($rawErrorCode))
                    : 'UNKNOWN_ERROR';

                $errorCode = substr($errorCode, 0, 100);

                $messages = [
                    'TEMP_HIGH' => 'Suhu mesin melewati batas.',
                    'DISPENSE_FAILED' => 'Produk gagal dikeluarkan.',
                    'DISPENSE_OUTCOME_UNKNOWN' =>
                        'Hasil dispense belum dapat dipastikan.',
                ];

                $message = $payload['message']
                    ?? $messages[$errorCode]
                    ?? "Machine error: {$errorCode}";

                if (! is_string($message)) {
                    $message = "Machine error: {$errorCode}";
                }

                $severity = match ($errorCode) {
                    'TEMP_HIGH',
                    'TEMPERATURE_HIGH',
                    'OVER_TEMPERATURE' => 'CRITICAL',
                    'DISPENSE_FAILED' => 'HIGH',
                    'DISPENSE_OUTCOME_UNKNOWN' => 'WARNING',
                    default => 'WARNING',
                };

                $activeError = MachineError::query()
                    ->where('machine_id', $machineId)
                    ->where('error_code', $errorCode)
                    ->whereNull('resolved_at')
                    ->first();

                if ($activeError) {
                    $activeError->update([
                        'message' => $message,
                        'severity' => $severity,
                    ]);
                } else {
                    MachineError::create([
                        'machine_id' => $machineId,
                        'error_code' => $errorCode,
                        'message' => $message,
                        'severity' => $severity,
                        'created_at' => now(),
                        'resolved_at' => null,
                    ]);
                }

                Telemetry::create([
                    'machine_id' => $machineId,
                    'temperature' => null,
                    'state' => 'ERROR',
                    'door_status' => null,
                    'created_at' => now(),
                ]);

                // Only a confirmed failure releases reserved stock.
                $dispenseId = $payload['dispense_id'] ?? null;

                if (
                    $errorCode === 'DISPENSE_FAILED'
                    && is_numeric($dispenseId)
                    && (int) $dispenseId > 0
                ) {
                    $dispense = Dispense::query()
                        ->whereKey((int) $dispenseId)
                        ->where('machine_id', $machineId)
                        ->first();

                    if ($dispense) {
                        $this->dispenseService
                            ->failDispense($dispense);
                    }
                }

                break;
        }

        Log::info('MQTT telemetry ingested.', [
            'topic' => $topic,
            'machine_id' => $machineId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MQTT message processed.',
            'topic' => $topic,
            'machine_id' => $machineId,
        ]);
    }
}
