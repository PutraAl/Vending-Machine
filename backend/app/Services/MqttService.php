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

        if (
            ! is_string($username)
            || trim($username) === ''
            || ! is_string($password)
            || $password === ''
        ) {
            throw new RuntimeException(
                'MQTT publisher credentials are not configured.'
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
            )
        ) {
            throw new RuntimeException(
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
            )
            ->setUsername($username)
            ->setPassword($password);

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

    public function publish(string $topic, array $payload): void
    {
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

            // QoS 0 is retained temporarily.
            // QoS 1 will be introduced with explicit PUBACK handling.
            $client->publish(
                $topic,
                $message,
                MqttClient::QOS_AT_MOST_ONCE,
                false
            );

            Log::info('MQTT message published.', [
                'topic' => $topic,
                'payload' => $payload,
            ]);

        } catch (Throwable $exception) {

            Log::error('MQTT publish failed.', [
                'topic' => $topic,
                'payload' => $payload,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Failed to publish MQTT message.',
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
        string $slot,
        int $quantity
    ): void {
        $this->publish(
            config('mqtt.topics.command'),
            [
                'command' => 'DISPENSE',
                'dispense_id' => $dispenseId,
                'slot' => $slot,
                'quantity' => $quantity,
            ]
        );
    }
}
