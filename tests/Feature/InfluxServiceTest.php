<?php

use App\Services\InfluxService;

test('the influx config is read from the environment', function () {
    expect(config('influxdb'))->toHaveKeys(['url', 'token', 'org', 'bucket', 'measurement', 'timeout', 'verify_ssl'])
        ->and(config('influxdb.measurement'))->toBe('sensor_data');
});

test('an invalid query range is rejected before contacting influxdb', function (string $range) {
    expect(fn () => app(InfluxService::class)->querySensorData('ESP32-BAMER-01', $range))
        ->toThrow(InvalidArgumentException::class);
})->with(['1 hour', '-1h) |> drop()', 'yesterday', '']);

test('a reading without any value is rejected before contacting influxdb', function () {
    expect(fn () => app(InfluxService::class)->writeSensorData('ESP32-BAMER-01', ['note' => null, 'nested' => ['a' => 1]]))
        ->toThrow(InvalidArgumentException::class);
});

test('ping reports an unreachable server instead of throwing', function () {
    config(['influxdb.url' => 'http://127.0.0.1:1', 'influxdb.timeout' => 1]);

    expect(app(InfluxService::class)->ping())->toMatchArray(['ok' => false, 'version' => null])
        ->and(app(InfluxService::class)->ping()['error'])->not->toBeNull();
});

test('the test route writes a point and reads it back', function () {
    $this->mock(InfluxService::class, function ($mock): void {
        $mock->shouldReceive('ping')->andReturn(['ok' => true, 'url' => 'http://influxdb:8086', 'version' => 'v2.7.12', 'error' => null]);
        $mock->shouldReceive('writeSensorData')->once()->withArgs(fn (string $device, array $data): bool => $device === 'test-influx' && isset($data['soil_percent']));
        $mock->shouldReceive('querySensorData')->once()->with('test-influx', '-5m', 5)->andReturn([['device_id' => 'test-influx', 'soil_percent' => 42]]);
    });

    $this->getJson(route('test-influx'))
        ->assertOk()
        ->assertJson(['ok' => true, 'written_and_read_back' => true, 'bucket' => config('influxdb.bucket')]);
});

test('the test route answers 503 when influxdb is down', function () {
    $this->mock(InfluxService::class)
        ->shouldReceive('ping')->andReturn(['ok' => false, 'url' => 'http://influxdb:8086', 'version' => null, 'error' => 'Connection refused']);

    $this->getJson(route('test-influx'))->assertServiceUnavailable()->assertJsonPath('ping.error', 'Connection refused');
});
