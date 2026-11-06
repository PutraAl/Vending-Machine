
<?php

return [
    'ingest_token' => env('MQTT_INGEST_TOKEN'),
    'host' => env('MQTT_BROKER', '127.0.0.1'),

    'port' => (int) env('MQTT_PORT', 8883),

    // MQTT client Laravel publisher.
    'client_id' => env(
        'MQTT_PUBLISHER_CLIENT_ID',
        env('MQTT_CLIENT_ID', 'laravel-vending-publisher')
    ),

    // MQTT client Laravel listener.
    'listener_client_id' => env(
        'MQTT_LISTENER_CLIENT_ID',
        'laravel-vending-listener'
    ),

    'protocol' => env('MQTT_PROTOCOL', '3.1.1'),

    /*
    |--------------------------------------------------------------------------
    | Legacy credentials
    |--------------------------------------------------------------------------
    |
    | Dipertahankan sementara untuk kompatibilitas konfigurasi lama.
    |
    */

    'username' => env('MQTT_USERNAME'),

    'password' => env('MQTT_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Separate MQTT credentials
    |--------------------------------------------------------------------------
    */

    'publisher' => [
        'username' => env(
            'MQTT_PUBLISHER_USERNAME',
            env('MQTT_USERNAME')
        ),

        'password' => env(
            'MQTT_PUBLISHER_PASSWORD',
            env('MQTT_PASSWORD')
        ),
    ],

    'listener' => [
        'username' => env(
            'MQTT_LISTENER_USERNAME',
            env('MQTT_USERNAME')
        ),

        'password' => env(
            'MQTT_LISTENER_PASSWORD',
            env('MQTT_PASSWORD')
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection settings
    |--------------------------------------------------------------------------
    */

    'keep_alive' => (int) env(
        'MQTT_KEEP_ALIVE',
        60
    ),

    'connect_timeout' => (int) env(
        'MQTT_CONNECT_TIMEOUT',
        10
    ),

    'socket_timeout' => (int) env(
        'MQTT_SOCKET_TIMEOUT',
        5
    ),

    'clean_session' => env(
        'MQTT_CLEAN_SESSION',
        true
    ),

    /*
    |--------------------------------------------------------------------------
    | TLS settings
    |--------------------------------------------------------------------------
    */

    'tls' => [

        'enabled' => (bool) env(
            'MQTT_TLS_ENABLED',
            true
        ),

        'verify_peer' => (bool) env(
            'MQTT_TLS_VERIFY_PEER',
            true
        ),

        'verify_peer_name' => (bool) env(
            'MQTT_TLS_VERIFY_PEER_NAME',
            true
        ),

        'allow_self_signed' => (bool) env(
            'MQTT_TLS_ALLOW_SELF_SIGNED',
            false
        ),

        'ca_file' => env('MQTT_TLS_CA_FILE'),

        'ca_path' => env('MQTT_TLS_CA_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MQTT topics
    |--------------------------------------------------------------------------
    */

    'topics' => [
        'command' => 'vending/machine/command',
        'status' => 'vending/machine/status',
        'temperature' => 'vending/machine/temperature',
        'inventory' => 'vending/machine/inventory',
        'error' => 'vending/machine/error',
    ],

];
