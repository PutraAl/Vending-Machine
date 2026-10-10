<?php

namespace App\Console\Commands;

use App\Models\Dispense;
use App\Models\MachineSlot;
use App\Models\Telemetry;
use App\Services\DispenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class MqttListenCommand extends Command
{
    protected $signature = 'mqtt:listen';

    protected $description = 'Listen for vending machine MQTT messages';

    public function __construct(
        private readonly DispenseService $dispenseService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $host = config('mqtt.host');
        $port = (int) config('mqtt.port', 1883);

        $clientId = config(
            'mqtt.listener_client_id',
            'laravel-vending-listener'
        );

        $statusTopic = config(
            'mqtt.topics.status',
            'vending/machine/status'
        );

        $errorTopic = config(
            'mqtt.topics.error',
            'vending/machine/error'
        );

        $temperatureTopic = config(
            'mqtt.topics.temperature',
            'vending/machine/temperature'
        );

        $inventoryTopic = config(
            'mqtt.topics.inventory',
            'vending/machine/inventory'
        );

        $connectionSettings = new ConnectionSettings();

        $connectionSettings->setKeepAliveInterval(
            (int) config('mqtt.keep_alive', 60)
        );

        $username = config('mqtt.username');
        $password = config('mqtt.password');

        if ($username !== null && $username !== '') {
            $connectionSettings->setUsername($username);
        }

        if ($password !== null && $password !== '') {
            $connectionSettings->setPassword($password);
        }

        $mqtt = new MqttClient(
            $host,
            $port,
            $clientId
        );

        try {
            $this->info(
                "Connecting to MQTT broker {$host}:{$port}..."
            );

            $mqtt->connect(
                $connectionSettings,
                (bool) config('mqtt.clean_session', true)
            );

            $this->info('Connected to MQTT broker.');

            /*
             * Machine status
             */
            $mqtt->subscribe(
                $statusTopic,
                function (
                    string $topic,
                    string $message,
                    bool $retained
                ): void {
                    $this->handleStatusMessage(
                        $topic,
                        $message
                    );
                },
                0
            );

            /*
             * Machine error
             */
            $mqtt->subscribe(
                $errorTopic,
                function (
                    string $topic,
                    string $message,
                    bool $retained
                ): void {
                    $this->handleErrorMessage(
                        $topic,
                        $message
                    );
                },
                0
            );

            /*
             * Temperature telemetry
             */
            $mqtt->subscribe(
                $temperatureTopic,
                function (
                    string $topic,
                    string $message,
                    bool $retained
                ): void {
                    $this->handleTemperatureMessage(
                        $topic,
                        $message
                    );
                },
                0
            );

            /*
             * Inventory telemetry
             */
            $mqtt->subscribe(
                $inventoryTopic,
                function (
                    string $topic,
                    string $message,
                    bool $retained
                ): void {
                    $this->handleInventoryMessage(
                        $topic,
                        $message
                    );
                },
                0
            );

            $this->info(
                "Subscribed to {$statusTopic}"
            );

            $this->info(
                "Subscribed to {$errorTopic}"
            );

            $this->info(
                "Subscribed to {$temperatureTopic}"
            );

            $this->info(
                "Subscribed to {$inventoryTopic}"
            );

            $this->info(
                'MQTT listener is running...'
            );

            $this->info(
                'Press CTRL+C to stop.'
            );

            $mqtt->loop(true);

            $mqtt->disconnect();

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('MQTT listener failed.', [
                'error' => $exception->getMessage(),
            ]);

            $this->error(
                'MQTT listener failed: '
                    . $exception->getMessage()
            );

            try {
                $mqtt->disconnect();
            } catch (Throwable) {
                // Ignore disconnect errors.
            }

            return self::FAILURE;
        }
    }

    private function handleStatusMessage(
        string $topic,
        string $message
    ): void {
        $this->line(
            "[MQTT] STATUS {$topic} → {$message}"
        );

        $payload = $this->decodePayload($message);

        if ($payload === null) {
            return;
        }

        $machineId = $this->getMachineId($payload);
        if (! $machineId) {
            $this->warn(
                '[MQTT] Error message has missing or invalid machine_id.'
            );

            return;
        }
        $state = $payload['state'] ?? null;
        $dispenseId = $payload['dispense_id'] ?? null;

        if (! $machineId || ! $state) {
            $this->warn(
                '[MQTT] Status message is missing machine_id or state.'
            );

            return;
        }

        Telemetry::create([
            'machine_id' => $machineId,
            'temperature' => null,
            'state' => (string) $state,
            'door_status' => null,
            'created_at' => now(),
        ]);

        $this->info(
            "[MQTT] Machine {$machineId} state: {$state}"
        );

        if (
            $state === 'DONE'
            && $dispenseId !== null
        ) {
            $this->completeDispense(
                (int) $dispenseId,
                $machineId
            );

            return;
        }

        if (
            in_array(
                $state,
                ['VALIDATING', 'DISPENSING'],
                true
            )
            && $dispenseId !== null
        ) {
            $this->info(
                "[MQTT] Dispense {$dispenseId} state: {$state}"
            );
        }
    }

    private function handleErrorMessage(
        string $topic,
        string $message
    ): void {
        $this->line(
            "[MQTT] ERROR {$topic} → {$message}"
        );

        $payload = $this->decodePayload($message);

        if ($payload === null) {
            return;
        }

        $machineId = $this->getMachineId($payload);
        if (! $machineId) {
            $this->warn(
                '[MQTT] Error message has missing or invalid machine_id.'
            );

            return;
        }

        if ($machineId) {
            /*
             * Store ERROR as telemetry.
             */
            Telemetry::create([
                'machine_id' => $machineId,
                'temperature' => null,
                'state' => 'ERROR',
                'door_status' => null,
                'created_at' => now(),
            ]);
        }

        $dispenseId = $payload['dispense_id'] ?? null;

        if (! $dispenseId) {
            $this->warn(
                '[MQTT] Error message has no dispense_id.'
            );

            return;
        }

        $this->failDispense(
            (int) $dispenseId,
            $machineId
        );
    }

    private function handleTemperatureMessage(
        string $topic,
        string $message
    ): void {
        $this->line(
            "[MQTT] TEMPERATURE {$topic} → {$message}"
        );

        $payload = $this->decodePayload($message);

        if ($payload === null) {
            return;
        }

        $machineId = $this->getMachineId($payload);
        if (! $machineId) {
            $this->warn(
                '[MQTT] Error message has missing or invalid machine_id.'
            );

            return;
        }
        $temperature = $payload['temperature'] ?? null;

        if (
            ! $machineId
            || ! is_numeric($temperature)
        ) {
            $this->warn(
                '[MQTT] Temperature message is invalid.'
            );

            return;
        }

        /*
         * The telemetry table requires state.
         * Use the latest known machine state when available.
         * If there is no previous telemetry, default to IDLE.
         */
        $latestTelemetry = Telemetry::query()
            ->where('machine_id', $machineId)
            ->latest('created_at')
            ->first();

        $state = $payload['state']
            ?? $latestTelemetry?->state
            ?? 'IDLE';

        Telemetry::create([
            'machine_id' => $machineId,
            'temperature' => $temperature,
            'state' => $state,
            'door_status' => null,
            'created_at' => now(),
        ]);

        $this->info(
            "[MQTT] Machine {$machineId} temperature: {$temperature}"
        );
    }

    private function handleInventoryMessage(
        string $topic,
        string $message
    ): void {
        $this->line(
            "[MQTT] INVENTORY {$topic} → {$message}"
        );

        $payload = $this->decodePayload($message);

        if ($payload === null) {
            return;
        }

        $machineId = $this->getMachineId($payload);
        if (! $machineId) {
            $this->warn(
                '[MQTT] Error message has missing or invalid machine_id.'
            );

            return;
        }
        $slotCode = $payload['slot'] ?? null;
        $currentQty = $payload['current_qty'] ?? null;

        if (
            ! $machineId
            || ! $slotCode
            || ! is_numeric($currentQty)
        ) {
            $this->warn(
                '[MQTT] Inventory message is invalid.'
            );

            return;
        }

        $currentQty = (int) $currentQty;

        if ($currentQty < 0) {
            $this->warn(
                '[MQTT] Current quantity cannot be negative.'
            );

            return;
        }

        $slot = MachineSlot::query()
            ->where('machine_id', $machineId)
            ->where('slot_code', $slotCode)
            ->first();

        if (! $slot) {
            $this->warn(
                "[MQTT] Slot {$slotCode} on machine {$machineId} was not found."
            );

            return;
        }

        /*
         * Never allow telemetry to overwrite a reservation.
         */
        /*
 * Inventory MQTT is telemetry only.
 * The database remains the source of truth for business stock.
 * Never update current_qty from simulator telemetry.
 */

        if ($currentQty < $slot->hold_qty) {
            $this->warn(
                "[MQTT] Reported inventory for {$slotCode} "
                    . "is below hold_qty. Database stock remains unchanged."
            );

            return;
        }

        if ($currentQty > $slot->capacity) {
            $this->warn(
                "[MQTT] Reported inventory for {$slotCode} "
                    . "exceeds capacity. Database stock remains unchanged."
            );

            return;
        }

        $this->info(
            "[MQTT] Inventory telemetry received "
                . "(business stock unchanged): "
                . "machine={$machineId}, "
                . "slot={$slotCode}, "
                . "reported_qty={$currentQty}, "
                . "database_current_qty={$slot->current_qty}"
        );
    }

    private function completeDispense(
        int $dispenseId,
        int $machineId
    ): void {
        $dispense = Dispense::query()
            ->whereKey($dispenseId)
            ->where('machine_id', $machineId)
            ->first();
        if (! $dispense) {
            $this->warn(
                "[MQTT] Dispense {$dispenseId} not found."
            );

            return;
        }

        if ($dispense->status === 'COMPLETED') {
            $this->info(
                "[MQTT] Dispense {$dispenseId} already completed."
            );

            return;
        }

        try {
            $this->dispenseService
                ->completeDispense($dispense);

            $this->info(
                "[MQTT] Dispense {$dispenseId} completed."
            );
        } catch (Throwable $exception) {
            Log::error(
                'Failed to complete dispense from MQTT status.',
                [
                    'dispense_id' => $dispenseId,
                    'error' => $exception->getMessage(),
                ]
            );

            $this->error(
                "[MQTT] Failed to complete dispense {$dispenseId}: "
                    . $exception->getMessage()
            );
        }
    }

    private function failDispense(
        int $dispenseId,
        int $machineId

    ): void {
        $dispense = Dispense::query()
            ->whereKey($dispenseId)
            ->where('machine_id', $machineId)
            ->first();

        if (! $dispense) {
            $this->warn(
                "[MQTT] Dispense {$dispenseId} not found."
            );

            return;
        }

        if ($dispense->status === 'FAILED') {
            $this->info(
                "[MQTT] Dispense {$dispenseId} already failed."
            );

            return;
        }

        try {
            $this->dispenseService
                ->failDispense($dispense);

            $this->info(
                "[MQTT] Dispense {$dispenseId} failed."
            );
        } catch (Throwable $exception) {
            Log::error(
                'Failed to fail dispense from MQTT error.',
                [
                    'dispense_id' => $dispenseId,
                    'error' => $exception->getMessage(),
                ]
            );

            $this->error(
                "[MQTT] Failed to mark dispense {$dispenseId}: "
                    . $exception->getMessage()
            );
        }
    }

    private function decodePayload(
        string $message
    ): ?array {
        $payload = json_decode(
            $message,
            true
        );

        if (! is_array($payload)) {
            $this->error(
                '[MQTT] Invalid JSON payload.'
            );

            return null;
        }

        return $payload;
    }

    private function getMachineId(
        array $payload
    ): ?int {
        $machineId = $payload['machine_id'] ?? null;

        if (
            $machineId === null
            || ! is_numeric($machineId)
        ) {
            return null;
        }

        return (int) $machineId;
    }
}
