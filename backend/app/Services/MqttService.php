<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use RuntimeException;
use Throwable;

class MqttService
{
    public function publish(string $topic, array $payload): void
    {
        $host = config('mqtt.host');
        $port = (int) config('mqtt.port', 1883);
        $clientId = config('mqtt.client_id');

        if (! $host || ! $clientId) {
            throw new RuntimeException(
                'MQTT configuration is incomplete.'
            );
        }

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

        $client = new MqttClient(
            $host,
            $port,
            $clientId
        );

        try {
            $client->connect(
                $connectionSettings,
                (bool) config('mqtt.clean_session', true)
            );

            $message = json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            );

            $client->publish(
                $topic,
                $message,
                0,
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