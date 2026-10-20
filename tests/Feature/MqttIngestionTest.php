<?php

use App\Models\SensorData;
use App\Services\SensorReadingIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * A message exactly as the MQTT v2 firmware publishes it.
 *
 * @param  array<string, mixed>  $overrides
 */
function firmwareMessage(array $overrides = []): string
{
    return json_encode(array_merge([
        'device_id' => 'ESP32-BAMER-01',
        'tanaman' => 'bawang_merah',
        'soil_percent' => 25,
        'soil_raw' => 2700,
        'temperature' => 29.4,
        'humidity' => 71,
        'water_raw' => 1,
        'water_level_status' => 'OK',
        'is_water_empty' => false,
        'is_soil_dry' => true,
        'pump_status' => 'ON',
        'relay_d23' => true,
        'pump_command' => 'auto',
    ], $overrides));
}

function ingestMqttMessage(string $message): SensorData
{
    return app(SensorReadingIngestor::class)->ingest($message);
}

test('a firmware mqtt message is stored as a reading', function () {
    $reading = ingestMqttMessage(firmwareMessage());

    expect($reading->only(['soil_percent', 'soil_raw', 'water_raw', 'temperature', 'humidity', 'is_water_empty', 'is_soil_dry', 'pump_status', 'pump_command']))
        ->toBe([
            'soil_percent' => 25,
            'soil_raw' => 2700,
            'water_raw' => 1,
            'temperature' => 29.4,
            'humidity' => 71.0,
            'is_water_empty' => false,
            'is_soil_dry' => true,
            'pump_status' => true,
            'pump_command' => 'auto',
        ]);
    $this->assertDatabaseCount('sensor_data', 1);
});

test('lamp states follow the firmware: d25 while pumping and d14 when the tank is empty', function (array $overrides, array $expected) {
    $reading = ingestMqttMessage(firmwareMessage($overrides));

    expect($reading->only(['pump_status', 'lampu1_d25', 'lampu2_d14']))->toBe($expected);
})->with([
    'auto watering' => [['relay_d23' => true], ['pump_status' => true, 'lampu1_d25' => true, 'lampu2_d14' => false]],
    'water empty' => [['is_water_empty' => true, 'relay_d23' => false, 'pump_status' => 'OFF'], ['pump_status' => false, 'lampu1_d25' => false, 'lampu2_d14' => true]],
    'soil wet' => [['soil_percent' => 70, 'is_soil_dry' => false, 'relay_d23' => false, 'pump_status' => 'OFF'], ['pump_status' => false, 'lampu1_d25' => false, 'lampu2_d14' => false]],
]);

test('relay_d23 is the real pump state and the ON/OFF string is used without it', function () {
    expect(ingestMqttMessage(firmwareMessage(['relay_d23' => false, 'pump_status' => 'ON']))->pump_status)->toBeFalse();

    $withoutRelay = json_decode(firmwareMessage(['pump_status' => 'ON']), true);
    unset($withoutRelay['relay_d23']);

    expect(ingestMqttMessage(json_encode($withoutRelay))->pump_status)->toBeTrue();
});

test('a failed dht read sent as zeros is stored without temperature and humidity', function () {
    $reading = ingestMqttMessage(firmwareMessage(['temperature' => 0, 'humidity' => 0]));

    expect($reading->temperature)->toBeNull()
        ->and($reading->humidity)->toBeNull();
});

test('invalid messages are rejected and nothing is stored', function (string $message, string $exception) {
    expect(fn () => ingestMqttMessage($message))->toThrow($exception);

    $this->assertDatabaseCount('sensor_data', 0);
})->with([
    'not json' => ['hello', JsonException::class],
    'not an object' => ['42', ValidationException::class],
    'soil out of range' => [firmwareMessage(['soil_percent' => 500]), ValidationException::class],
    'impossible dht values' => [firmwareMessage(['temperature' => 120, 'humidity' => 140]), ValidationException::class],
    'missing soil' => [json_encode(['is_water_empty' => false]), ValidationException::class],
]);
