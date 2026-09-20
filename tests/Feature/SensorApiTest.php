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
