<?php

use App\Models\SensorData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return list<list<string>>
 */
function csvRows(string $content): array
{
    $lines = preg_split('/\r?\n/', trim(ltrim($content, "\xEF\xBB\xBF")));

    return array_map(fn (string $line): array => str_getcsv($line), $lines);
}

test('guests cannot download the dataset', function () {
    $this->get(route('sensor.export', ['datasets' => ['soil']]))->assertRedirect(route('login'));
});

test('csv only contains the selected datasets within the date range, oldest first', function () {
    SensorData::factory()->create(['soil_percent' => 11, 'created_at' => '2026-10-01 08:00:00']);
    SensorData::factory()->create(['soil_percent' => 22, 'water_raw' => 2000, 'created_at' => '2026-10-02 09:30:00']);
    SensorData::factory()->soilDry()->create(['soil_percent' => 15, 'created_at' => '2026-10-03 23:59:59']);
    SensorData::factory()->create(['soil_percent' => 44, 'created_at' => '2026-10-04 00:00:00']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('sensor.export', [
            'datasets' => ['soil', 'status'],
            'date_from' => '2026-10-02',
            'date_to' => '2026-10-03',
        ]))
        ->assertOk()
        ->assertDownload('dataset-sensor_2026-10-02_sd_2026-10-03.csv');

    $rows = csvRows($response->streamedContent());

    expect($rows[0])->toBe(['timestamp', 'soil_percent', 'soil_raw', 'status'])
        ->and($rows)->toHaveCount(3)
        ->and($rows[1][0])->toBe('2026-10-02T02:30:00Z')
        ->and($rows[1][1])->toBe('22')
        ->and($rows[2][1])->toBe('15')
        ->and($rows[2][3])->toBe('MULAI AUTO');
});

test('csv can be filtered by status and keeps the canonical column order', function () {
    SensorData::factory()->create();
    SensorData::factory()->waterEmpty()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('sensor.export', ['datasets' => ['buzzer', 'water'], 'status' => 'empty']))
        ->assertOk();

    $rows = csvRows($response->streamedContent());

    expect($rows[0])->toBe(['timestamp', 'is_water_empty', 'alarm'])
        ->and($rows)->toHaveCount(2)
        ->and(array_slice($rows[1], 1))->toBe(['true', 'true']);
});

test('export requires at least one valid dataset and a valid date range', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->get(route('sensor.export'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrorsIn('export', 'datasets');

    $this->actingAs($user)
        ->get(route('sensor.export', ['datasets' => ['unknown']]))
        ->assertSessionHasErrorsIn('export', 'datasets.0');

    $this->actingAs($user)
        ->get(route('sensor.export', ['datasets' => ['soil'], 'date_from' => '2026-10-05', 'date_to' => '2026-10-01']))
        ->assertSessionHasErrorsIn('export', 'date_to');
});

test('dashboard shows the dataset download form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Download Dataset CSV')
        ->assertSee('Kelembaban Tanah')
        ->assertSee('Pin D34');
});

test('csv can include soil temperature and buzzer alarm', function () {
    SensorData::factory()->create(['soil_temperature' => 28.5, 'buzzer_alarm' => true]);
    SensorData::factory()->withoutSoilTemperature()->create(['buzzer_alarm' => null]);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('sensor.export', ['datasets' => ['soil_temperature', 'buzzer']]))
        ->assertOk();

    $rows = csvRows($response->streamedContent());

    expect($rows[0])->toBe(['timestamp', 'soil_temperature', 'alarm'])
        ->and(array_slice($rows[1], 1))->toBe(['28.5', 'true'])
        ->and(array_slice($rows[2], 1))->toBe(['', '']);
});

test('dht air temperature and humidity can no longer be exported', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('sensor.export', ['datasets' => ['climate']]))
        ->assertSessionHasErrorsIn('export', 'datasets.0');
});

test('the status column reports what the pump really did, not just a dry soil', function (array $attributes, string $expected) {
    expect(SensorData::factory()->make($attributes)->statusLabel())->toBe($expected);
})->with([
    'dry soil waiting for the 2 hour pause' => [['is_soil_dry' => true, 'pump_status' => false, 'pump_command' => 'auto'], 'Kering - Tunggu Jeda'],
    'auto watering' => [['is_soil_dry' => true, 'pump_status' => true, 'pump_command' => 'auto'], 'Menyiram'],
    'manual on' => [['pump_status' => true, 'pump_command' => 'pump_on'], 'Manual ON'],
    'manual off with dry soil' => [['is_soil_dry' => true, 'pump_status' => false, 'pump_command' => 'pump_off'], 'Manual OFF'],
    'tank empty' => [['is_water_empty' => true, 'is_soil_dry' => true, 'pump_status' => false, 'pump_command' => 'auto'], 'Air Habis'],
    'wet soil' => [['pump_command' => 'auto'], 'Aman - Basah'],
]);

test('the pump csv dataset includes the pump status and command', function () {
    SensorData::factory()->create(['pump_status' => true, 'pump_command' => 'pump_on']);
    SensorData::factory()->create(['pump_command' => null]);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('sensor.export', ['datasets' => ['pump']]))
        ->assertOk();

    $rows = csvRows($response->streamedContent());

    expect($rows[0])->toBe(['timestamp', 'pump_status', 'pump_command'])
        ->and(array_slice($rows[1], 1))->toBe(['ON', 'pump_on'])
        ->and(array_slice($rows[2], 1))->toBe(['OFF', '']);
});

test('a full csv follows the dataset structure', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get(route('sensor.export', ['datasets' => ['buzzer', 'status', 'pump', 'water', 'soil_temperature', 'soil']]))
        ->assertOk();

    expect(csvRows($response->streamedContent())[0])->toBe([
        'timestamp', 'soil_percent', 'soil_raw', 'soil_temperature', 'is_water_empty', 'pump_status', 'pump_command', 'status', 'alarm',
    ]);
});

test('the status column names the automation step like the firmware', function () {
    SensorData::factory()->soilDry()->create(['pump_status' => false]);
    SensorData::factory()->soilDry()->create(['pump_status' => true]);
    SensorData::factory()->soilDry()->create(['pump_status' => true]);
    SensorData::factory()->soilDry()->create(['pump_status' => false]);
    SensorData::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('sensor.export', ['datasets' => ['status']]))
        ->assertOk();

    expect(array_column(array_slice(csvRows($response->streamedContent()), 1), 1))
        ->toBe(['TUNGGU', 'MULAI AUTO', 'AUTO SIRAM', 'SELESAI', 'LEMBAB AMAN']);
});
