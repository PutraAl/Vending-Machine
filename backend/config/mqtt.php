<?php

return [

    'host' => env('MQTT_BROKER', '127.0.0.1'),

    'port' => (int) env('MQTT_PORT', 1883),

    // Client ID khusus publisher Laravel
    'client_id' => env(
        'MQTT_CLIENT_ID',
        'laravel-vending-publisher'
    ),

    // Client ID khusus listener Laravel
    'listener_client_id' => env(
        'MQTT_LISTENER_CLIENT_ID',
        'laravel-vending-listener'
    ),

    'protocol' => env(
        'MQTT_PROTOCOL',
        '3.1.1'
    ),

    'username' => env('MQTT_USERNAME'),

    'password' => env('MQTT_PASSWORD'),

    'keep_alive' => (int) env(
        'MQTT_KEEP_ALIVE',
        60
    ),

    'clean_session' => env(
        'MQTT_CLEAN_SESSION',
        true
    ),

    'topics' => [
        'command' => 'vending/machine/command',
        'status' => 'vending/machine/status',
        'temperature' => 'vending/machine/temperature',
        'inventory' => 'vending/machine/inventory',
        'error' => 'vending/machine/error',
    ],

];