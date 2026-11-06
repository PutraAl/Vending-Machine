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
use App\Models\Machine;
use App\Models\MachineError;

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


        $username = config('mqtt.listener.username');
        $password = config('mqtt.listener.password');
        $hasUsername = is_string($username) && trim($username) !== '';
        $hasPassword = is_string($password) && $password !== '';

        if ($hasUsername !== $hasPassword) {
            throw new \RuntimeException(
                'MQTT listener username and password must be configured together.'
            );
        }

        $tlsEnabled = (bool) config('mqtt.tls.enabled', false);

        $verifyPeer = (bool) config(
            'mqtt.tls.verify_peer',
            true
        );

        $verifyPeerName = (bool) config(
            'mqtt.tls.verify_peer_name',
            true
        );

        $allowSelfSigned = (bool) config(
            'mqtt.tls.allow_self_signed',
            false
        );

        if (
            app()->environment('production')
            && (
                ! $tlsEnabled
                || ! $verifyPeer
                || ! $verifyPeerName
                || $allowSelfSigned
                || ! $hasUsername
            )
        ) {
            throw new \RuntimeException(
                'Secure MQTT TLS configuration is required in production.'
            );
        }

        $caFile = config('mqtt.tls.ca_file');
        $caPath = config('mqtt.tls.ca_path');

        if (
            $tlsEnabled
            && is_string($caFile)
            && $caFile !== ''
            && ! is_file($caFile)
        ) {
            throw new \RuntimeException(
                'MQTT TLS CA certificate file does not exist.'
            );
        }

        if (
            $tlsEnabled
            && is_string($caPath)
            && $caPath !== ''
            && ! is_dir($caPath)
        ) {
            throw new \RuntimeException(
                'MQTT TLS CA certificate directory does not exist.'
            );
        }

        // ConnectionSettings is immutable.
        $connectionSettings = (new ConnectionSettings())
            ->setKeepAliveInterval(
                (int) config('mqtt.keep_alive', 60)
            )
            ->setConnectTimeout(
                (int) config('mqtt.connect_timeout', 10)
            )
            ->setSocketTimeout(
                (int) config('mqtt.socket_timeout', 5)
            );

        if ($hasUsername) {
            $connectionSettings = $connectionSettings
                ->setUsername($username)
                ->setPassword((string) $password);
        }

        if ($tlsEnabled) {
            $connectionSettings = $connectionSettings
                ->setUseTls(true)
                ->setTlsVerifyPeer($verifyPeer)
                ->setTlsVerifyPeerName($verifyPeerName)
                ->setTlsSelfSignedAllowed($allowSelfSigned);

            if (
                is_string($caFile)
                && $caFile !== ''
            ) {
                $connectionSettings = $connectionSettings
                    ->setTlsCertificateAuthorityFile($caFile);
            }

            if (
                is_string($caPath)
                && $caPath !== ''
            ) {
                $connectionSettings = $connectionSettings
                    ->setTlsCertificateAuthorityPath($caPath);
            }
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
                    $this->safely(
                        'status',
                        fn () => $this->handleStatusMessage($topic, $message)
                    );
                },
                1
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
                    $this->safely(
                        'error',
                        fn () => $this->handleErrorMessage($topic, $message)
                    );
                },
                1
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
                    $this->safely(
                        'temperature',
                        fn () => $this->handleTemperatureMessage($topic, $message)
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
                    $this->safely(
                        'inventory',
                        fn () => $this->handleInventoryMessage($topic, $message)
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
                '[MQTT] Status message has missing or invalid machine_id.'
            );

            return;
        }

        if (! Machine::whereKey($machineId)->exists()) {
            $this->warn("[MQTT] Machine {$machineId} was not found.");

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
        $this->line("[MQTT] ERROR {$topic} -> {$message}");

        $payload = $this->decodePayload($message);

        if ($payload === null) {
            return;
        }

        $machineId = $this->getMachineId($payload);

        if (! $machineId) {
            $this->warn('[MQTT] Invalid machine_id.');
            return;
        }

        if (! Machine::whereKey($machineId)->exists()) {
            $this->warn("[MQTT] Machine {$machineId} was not found.");
            return;
        }

        $rawErrorCode = $payload['error']
            ?? $payload['error_code']
            ?? null;

        $errorCode = is_string($rawErrorCode)
            && trim($rawErrorCode) !== ''
            ? strtoupper(trim($rawErrorCode))
            : 'UNKNOWN_ERROR';

        $errorCode = substr($errorCode, 0, 100);

        $dispenseId = $payload['dispense_id'] ?? null;
        $slot = $payload['slot'] ?? null;

        $messages = [
            'TEMP_HIGH' =>
            'Suhu mesin melewati batas yang ditentukan.',
            'DISPENSE_FAILED' =>
            'Produk gagal dikeluarkan oleh mesin.',
            'DISPENSE_OUTCOME_UNKNOWN' =>
            'Hasil dispense belum dapat dipastikan. Perlu rekonsiliasi.',
            'INVALID_COMMAND_JSON' =>
            'Mesin menerima pesan JSON yang tidak valid.',
            'INVALID_DISPENSE_COMMAND' =>
            'Mesin menerima perintah dispense yang tidak valid.',
        ];

        $providedMessage = $payload['message'] ?? null;

        $errorMessage = is_string($providedMessage)
            && trim($providedMessage) !== ''
            ? trim($providedMessage)
            : ($messages[$errorCode]
                ?? "Mesin melaporkan error: {$errorCode}.");

        if (is_string($slot) && trim($slot) !== '') {
            $errorMessage .= ' Slot: ' . trim($slot) . '.';
        }

        if (is_numeric($dispenseId) && (int) $dispenseId > 0) {
            $errorMessage .= ' Dispense ID: ' . (int) $dispenseId . '.';
        }

        $severity = match ($errorCode) {
            'TEMP_HIGH',
            'TEMPERATURE_HIGH',
            'OVER_TEMPERATURE' => 'CRITICAL',

            'DISPENSE_FAILED' => 'HIGH',

            'DISPENSE_OUTCOME_UNKNOWN' => 'WARNING',

            'INVALID_COMMAND_JSON',
            'INVALID_DISPENSE_COMMAND' => 'MEDIUM',

            default => 'WARNING',
        };

        /*
     * Hindari membuat banyak catatan aktif untuk error
     * yang sama pada mesin yang sama.
     * Jika sudah selesai lalu muncul lagi, buat catatan baru.
     */
        $activeError = MachineError::query()
            ->where('machine_id', $machineId)
            ->where('error_code', $errorCode)
            ->whereNull('resolved_at')
            ->first();

        if ($activeError) {
            $activeError->update([
                'message' => $errorMessage,
                'severity' => $severity,
            ]);

            $this->info(
                "[MQTT] Updated active error {$errorCode} "
                    . "for machine {$machineId}."
            );
        } else {
            MachineError::create([
                'machine_id' => $machineId,
                'error_code' => $errorCode,
                'message' => $errorMessage,
                'severity' => $severity,
                'created_at' => now(),
                'resolved_at' => null,
            ]);

            $this->warn(
                "[MQTT] Machine error {$errorCode} recorded "
                    . "for machine {$machineId}."
            );
        }

        // Pertahankan riwayat telemetry seperti sebelumnya.
        Telemetry::create([
            'machine_id' => $machineId,
            'temperature' => null,
            'state' => 'ERROR',
            'door_status' => null,
            'created_at' => now(),
        ]);

        /*
     * Error umum tetap dicatat meskipun tidak memiliki
     * dispense_id. Tanpa ID, jangan menyentuh dispense.
     */
        if (! is_numeric($dispenseId) || (int) $dispenseId < 1) {
            if (in_array($errorCode, [
                'DISPENSE_FAILED',
                'DISPENSE_OUTCOME_UNKNOWN',
            ], true)) {
                $this->warn(
                    "[MQTT] {$errorCode} has no valid dispense_id. "
                        . 'No dispense status or stock was changed.'
                );
            }

            return;
        }

        /*
     * Hasil tidak diketahui bukan berarti dispense gagal.
     * Reservasi harus tetap ada untuk rekonsiliasi.
     */
        if ($errorCode === 'DISPENSE_OUTCOME_UNKNOWN') {
            $this->warn(
                "[MQTT] Dispense {$dispenseId} outcome is unknown. "
                    . 'Reservation retained for reconciliation.'
            );

            return;
        }

        /*
     * Hanya kegagalan dispense yang terkonfirmasi
     * boleh menjalankan alur failDispense().
     */
        if ($errorCode !== 'DISPENSE_FAILED') {
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
                '[MQTT] Temperature message has missing or invalid machine_id.'
            );

            return;
        }

        if (! Machine::whereKey($machineId)->exists()) {
            $this->warn("[MQTT] Machine {$machineId} was not found.");

            return;
        }

        $temperature = $payload['temperature'] ?? null;

        if (! is_numeric($temperature)) {
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
                '[MQTT] Inventory message has missing or invalid machine_id.'
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

    /**
     * Run one message handler so that a failure while processing a single
     * message is logged instead of stopping the whole listener.
     */
    private function safely(string $label, callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            Log::error("MQTT {$label} handler failed.", [
                'error' => $exception->getMessage(),
            ]);

            $this->error(
                "[MQTT] {$label} handler failed: "
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
