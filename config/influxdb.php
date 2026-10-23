<?php

return [

    /*
    |--------------------------------------------------------------------------
    | InfluxDB 2.x
    |--------------------------------------------------------------------------
    |
    | Time-series storage for sensor readings, used by App\Services\InfluxService.
    | Inside Docker the app reaches InfluxDB by its service name
    | (http://influxdb:8086); from the host machine use http://localhost:8086.
    |
    */

    'url' => env('INFLUXDB_URL', 'http://localhost:8086'),

    'token' => env('INFLUXDB_TOKEN'),

    'org' => env('INFLUXDB_ORG', 'binus'),

    'bucket' => env('INFLUXDB_BUCKET', 'iot_sensors'),

    /*
    | Measurement (table) the sensor points are written to.
    */
    'measurement' => env('INFLUXDB_MEASUREMENT', 'sensor_data'),

    /*
    | HTTP timeout in seconds, and TLS verification for https:// URLs.
    */
    'timeout' => (int) env('INFLUXDB_TIMEOUT', 5),

    'verify_ssl' => (bool) env('INFLUXDB_VERIFY_SSL', true),

];
