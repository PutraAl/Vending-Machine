<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use RuntimeException;
use Throwable;

class MqttService
{
    private function buildConnectionSettings(): ConnectionSettings
    {
        $username = config('mqtt.publisher.username');
        $password = config('mqtt.publisher.password');
        $hasUsername = is_string($username) && trim($username) !== '';
        $hasPassword = is_string($password) && $password !== '';

        if ($hasUsername !== $hasPassword) {
            throw new RuntimeException(
                'MQTT publisher username and password must be configured together.'
            );
        }

        $tlsEnabled = (bool) config(
            'mqtt.tls.enabled',
            false
        );

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

        // Production must never connect without verified TLS.
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
            throw new RuntimeException(
                'Secure MQTT TLS configuration is required in production.'
            );
        }

        $caFile = config('mqtt.tls.ca_file');
        $caPath = config('mqtt.tls.ca_path');

        if (is_string($caFile) && trim($caFile) !== '') {
            $caFile = trim($caFile);

            $isAbsolutePath = str_starts_with($caFile, '/')
                || preg_match('/^[A-Za-z]:[\/\\\\]/', $caFile) === 1;

            if (! $isAbsolutePath) {
                $caFile = base_path($caFile);
            }
        }

        if (
            $tlsEnabled
            && is_string($caFile)
            && $caFile !== ''
            && ! is_file($caFile)
        ) {
            throw new RuntimeException(
                'MQTT TLS CA certificate file does not exist.'
            );
        }

        if (
            $tlsEnabled
            && is_string($caPath)
            && $caPath !== ''
            && ! is_dir($caPath)
        ) {
            throw new RuntimeException(
                'MQTT TLS CA certificate directory does not exist.'
            );
        }

        // ConnectionSettings is immutable.
        // Always assign the returned instance.
        $settings = (new ConnectionSettings())
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
            $settings = $settings
                ->setUsername($username)
                ->setPassword((string) $password);
        }

        if ($tlsEnabled) {
            $settings = $settings
                ->setUseTls(true)
                ->setTlsVerifyPeer($verifyPeer)
                ->setTlsVerifyPeerName($verifyPeerName)
                ->setTlsSelfSignedAllowed($allowSelfSigned);

            if (
                is_string($caFile)
                && $caFile !== ''
            ) {
                $settings = $settings
                    ->setTlsCertificateAuthorityFile($caFile);
            }

            if (
                is_string($caPath)
                && $caPath !== ''
            ) {
                $settings = $settings
                    ->setTlsCertificateAuthorityPath($caPath);
            }
        }

        return $settings;
    }

    public function publish(
        string $topic,
        array $payload,
        int $qos = MqttClient::QOS_AT_MOST_ONCE
    ): void {
        $host = config('mqtt.host');
        $port = (int) config('mqtt.port', 8883);
        $clientId = config('mqtt.client_id');

        if (
            ! is_string($host)
            || trim($host) === ''
            || ! is_string($clientId)
            || trim($clientId) === ''
        ) {
            throw new RuntimeException(
                'MQTT configuration is incomplete.'
            );
        }

        $connectionSettings = $this->buildConnectionSettings();

        $client = new MqttClient(
            $host,
            $port,
            $clientId,
            config('mqtt.protocol', MqttClient::MQTT_3_1_1)
        );

        try {
            $client->connect(
                $connectionSettings,
                (bool) config('mqtt.clean_session', true)
            );

            $message = json_encode(
                $payload,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
            );

            $client->publish(
                $topic,
                $message,
                $qos,
                false
            );

            Log::info('MQTT message published.', [
                'topic' => $topic,
                'payload' => $payload,
            ]);
        } catch (Throwable $exception) {

            $rootCause = $exception;

            while ($rootCause->getPrevious() !== null) {
                $rootCause = $rootCause->getPrevious();
            }

            $diagnostic = [
                'topic' => $topic,
                'host' => $host,
                'port' => $port,
                'client_id' => $clientId,
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'root_cause_class' => get_class($rootCause),
                'root_cause_message' => $rootCause->getMessage(),
                'root_cause_code' => $rootCause->getCode(),
            ];

            error_log(
                '[MQTT_PUBLISH_DIAGNOSTIC] '
                    . json_encode($diagnostic, JSON_UNESCAPED_SLASHES)
            );

            Log::error('MQTT publish failed.', $diagnostic);

            throw new RuntimeException(
                'Failed to publish MQTT message: ' . $rootCause->getMessage(),
                0,
                $exception
            );
        } finally {

            try {
                $client->disconnect();
            } catch (Throwable) {
                // Ignore disconnect errors.
            }
        }
    }

    public function publishDispenseCommand(
        int $dispenseId,
        int $machineId,
        string $slot,
        int $quantity
    ): void {
        $this->publish(
            config('mqtt.topics.command'),
            [
                'command' => 'DISPENSE',
                'dispense_id' => $dispenseId,
                'machine_id' => $machineId,
                'slot' => $slot,
                'quantity' => $quantity,
                'expires_at' => now('UTC')->addSeconds(55)->toIso8601String(),
            ],
            MqttClient::QOS_AT_LEAST_ONCE
        );
    }
}
