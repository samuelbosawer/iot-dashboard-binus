<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MQTT Broker
    |--------------------------------------------------------------------------
    |
    | The ESP32 publishes its readings to this broker and listens for pump
    | commands on it. Laravel stores readings through `php artisan mqtt:listen`
    | while browsers receive live data over the broker's WebSocket endpoint.
    |
    */

    'host' => env('MQTT_HOST', 'broker.hivemq.com'),

    'port' => (int) env('MQTT_PORT', 1883),

    'tls' => (bool) env('MQTT_TLS', false),

    'username' => env('MQTT_USERNAME'),

    'password' => env('MQTT_PASSWORD'),

    'client_id_prefix' => env('MQTT_CLIENT_ID_PREFIX', 'taman-pintar-web'),

    /*
    | Browsers can only speak MQTT over WebSocket; HTTPS pages need wss://.
    */
    'websocket_url' => env('MQTT_WEBSOCKET_URL', 'wss://broker.hivemq.com:8884/mqtt'),

    'topics' => [
        'sensor' => env('MQTT_TOPIC_SENSOR', 'binus/garden/sensor/data'),
        'pump' => env('MQTT_TOPIC_PUMP', 'binus/garden/pump/cmd'),
    ],

];
