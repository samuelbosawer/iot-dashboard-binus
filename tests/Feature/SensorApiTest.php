<?php

use App\Models\SensorData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function sensorPayload(array $overrides = []): array
{
    return array_merge([
        'soil_percent' => 25,
        'soil_raw' => 3100,
        'water_raw' => 2000,
        'is_water_empty' => false,
        'is_soil_dry' => true,
        'pump_status' => true,
        'lampu1_d25' => true,
        'lampu2_d14' => false,
    ], $overrides);
}

test('esp32 can store a reading with a valid api key', function () {
    $response = $this->postJson('/api/sensor', sensorPayload(), ['x-api-key' => 'binus-iot-2025']);

    $response->assertCreated()->assertJsonPath('soil_percent', 25);
    $this->assertDatabaseHas('sensor_data', ['soil_percent' => 25, 'is_soil_dry' => true, 'pump_status' => true]);
});

test('storing a reading without an api key is rejected', function () {
    $this->postJson('/api/sensor', sensorPayload())->assertUnauthorized();
    $this->postJson('/api/sensor', sensorPayload(), ['x-api-key' => 'wrong'])->assertUnauthorized();

    $this->assertDatabaseCount('sensor_data', 0);
});

test('invalid payloads return json validation errors even without an accept header', function () {
    $response = $this->post('/api/sensor', ['soil_percent' => 500], ['x-api-key' => 'binus-iot-2025']);

    $response->assertStatus(422)->assertJsonValidationErrors(['soil_percent', 'soil_raw', 'water_raw']);
});

test('latest returns the newest reading', function () {
    SensorData::factory()->create(['soil_percent' => 50]);
    SensorData::factory()->waterEmpty()->create(['soil_percent' => 70]);

    $this->getJson('/api/sensor/latest')
        ->assertOk()
        ->assertJsonPath('soil_percent', 70)
        ->assertJsonPath('is_water_empty', true);
});

test('latest returns 404 when there is no data', function () {
    $this->getJson('/api/sensor/latest')->assertNotFound();
});

test('history is limited and ordered oldest to newest', function () {
    SensorData::factory()->count(5)->sequence(
        ['soil_percent' => 10],
        ['soil_percent' => 20],
        ['soil_percent' => 30],
        ['soil_percent' => 40],
        ['soil_percent' => 50],
    )->create();

    $response = $this->getJson('/api/sensor/history?limit=3')->assertOk();

    expect(collect($response->json())->pluck('soil_percent')->all())->toBe([30, 40, 50]);
});

test('history with after_id only returns newer readings', function () {
    $readings = SensorData::factory()->count(4)->sequence(
        ['soil_percent' => 10],
        ['soil_percent' => 20],
        ['soil_percent' => 30],
        ['soil_percent' => 40],
    )->create();

    $response = $this->getJson('/api/sensor/history?after_id='.$readings[1]->id)->assertOk();

    expect(collect($response->json())->pluck('soil_percent')->all())->toBe([30, 40]);

    $this->getJson('/api/sensor/history?after_id='.$readings[3]->id)
        ->assertOk()
        ->assertExactJson([]);
});

test('index filters readings by date range and paginates newest first', function () {
    SensorData::factory()->create(['soil_percent' => 11, 'created_at' => '2026-09-01 08:00:00']);
    SensorData::factory()->create(['soil_percent' => 22, 'created_at' => '2026-09-02 23:59:00']);
    SensorData::factory()->create(['soil_percent' => 33, 'created_at' => '2026-09-03 00:00:00']);

    $response = $this->getJson('/api/sensor?date_from=2026-09-02&date_to=2026-09-03')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    expect(collect($response->json('data'))->pluck('soil_percent')->all())->toBe([33, 22]);
});

test('index filters readings by status', function () {
    SensorData::factory()->create();
    SensorData::factory()->soilDry()->create();
    SensorData::factory()->waterEmpty()->create();

    $this->getJson('/api/sensor?status=dry')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.is_soil_dry', true);
});

test('index rejects an end date before the start date', function () {
    $this->getJson('/api/sensor?date_from=2026-09-05&date_to=2026-09-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date_to');
});

test('dashboard requires authentication and renders for a logged in user', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    SensorData::factory()->soilDry()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('IoT Penyiram Tanaman Binus');
});

test('the default admin can log in after seeding', function () {
    $this->seed();

    $this->post(route('login'), ['email' => 'admin@binus.ac.id', 'password' => 'binus123'])
        ->assertRedirect(route('dashboard'));
});
