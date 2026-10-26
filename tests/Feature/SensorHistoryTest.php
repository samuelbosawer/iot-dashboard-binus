<?php

use App\Models\SensorData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the http api is gone because readings only arrive over mqtt', function (string $method, string $uri) {
    $this->json($method, $uri)->assertNotFound();
})->with([
    ['POST', '/api/sensor'],
    ['GET', '/api/sensor'],
    ['GET', '/api/sensor/latest'],
    ['GET', '/api/sensor/history'],
]);

test('the history table is filtered by date range and lists newest first', function () {
    SensorData::factory()->create(['soil_percent' => 11, 'created_at' => '2026-09-01 08:00:00']);
    SensorData::factory()->create(['soil_percent' => 22, 'created_at' => '2026-09-02 23:59:00']);
    SensorData::factory()->create(['soil_percent' => 33, 'created_at' => '2026-09-03 00:00:00']);

    $table = $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['date_from' => '2026-09-02', 'date_to' => '2026-09-03']))
        ->assertOk()
        ->viewData('table');

    expect($table['meta']['total'])->toBe(2)
        ->and(collect($table['data'])->pluck('soil_percent')->all())->toBe([33, 22])
        ->and($table['filters'])->toBe(['date_from' => '2026-09-02', 'date_to' => '2026-09-03', 'status' => null, 'q' => null]);
});

test('the history table is filtered by status and paginated with links that keep the filter', function () {
    SensorData::factory()->create();
    SensorData::factory()->count(12)->soilDry()->create();
    SensorData::factory()->waterEmpty()->create();

    $table = $this->get(route('visitor', ['status' => 'dry']))->assertOk()->viewData('table');

    expect($table['meta'])->toMatchArray(['total' => 12, 'per_page' => 10, 'current_page' => 1, 'last_page' => 2])
        ->and(collect($table['data'])->every(fn (array $row): bool => $row['is_soil_dry']))->toBeTrue()
        ->and($table['links']['prev'])->toBeNull()
        ->and($table['links']['next'])->toContain('status=dry')->toContain('page=2')->toEndWith('#tabel');
});

test('an invalid history filter is ignored and reported instead of redirecting', function () {
    SensorData::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['date_from' => '2026-09-05', 'date_to' => '2026-09-01']))
        ->assertOk()
        ->assertViewHas('table', fn (array $table): bool => $table['meta']['total'] === 2 && $table['error'] !== null)
        ->assertSee('Filter diabaikan.');
});

test('the dashboard preloads enough readings for every chart range', function () {
    SensorData::factory()->count(130)->create();

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();

    expect($response->viewData('history'))->toHaveCount(120)
        ->and($response->viewData('historyLimit'))->toBe(30);
});

test('pages no longer call the http api', function () {
    $this->get(route('visitor'))->assertOk()->assertDontSee('/api/sensor');

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertDontSee('/api/sensor');
});

test('dashboard requires authentication and renders for a logged in user', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    SensorData::factory()->soilDry()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('IoT Penyiram Tanaman')
        ->assertDontSee('Binus');
});

test('the default admin can log in after seeding', function () {
    $this->seed();

    $this->post(route('login'), ['email' => 'admin@binus.ac.id', 'password' => 'binus123'])
        ->assertRedirect(route('dashboard'));
});

test('the history table can be searched by number, time and keyword', function (string $term, array $expected) {
    SensorData::factory()->create(['soil_percent' => 35, 'soil_temperature' => 24.1, 'pump_command' => 'auto', 'created_at' => '2026-10-20 08:15:00']);
    SensorData::factory()->soilDry()->create(['soil_percent' => 20, 'soil_temperature' => 27.5, 'pump_command' => 'auto', 'created_at' => '2026-10-21 14:05:00']);
    SensorData::factory()->waterEmpty()->create(['soil_percent' => 60, 'soil_temperature' => 29.0, 'pump_command' => 'pump_off', 'created_at' => '2026-10-21 16:30:00']);

    $table = $this->get(route('visitor', ['q' => $term]))->assertOk()->viewData('table');

    expect(collect($table['data'])->pluck('soil_percent')->sort()->values()->all())->toBe($expected)
        ->and($table['filters']['q'])->toBe($term);
})->with([
    'soil percent' => ['35', [35]],
    'soil temperature with decimals' => ['27.5', [20]],
    'whole soil temperature' => ['29', [60]],
    'date' => ['2026-10-21', [20, 60]],
    'time' => ['14:05', [20]],
    'dry soil' => ['kering', [20]],
    'empty tank' => ['habis', [60]],
    'watering' => ['menyiram', [20]],
    'manual off mode' => ['manual off', [60]],
    'buzzer alarm' => ['alarm', [20, 60]],
    'nothing found' => ['xyz', []],
    'soil percent as shown' => ['35%', [35]],
    'soil temperature as shown' => ['27,5 °C', [20]],
    'date as shown in the table' => ['21 Oct 2026', [20, 60]],
    'local date format' => ['20/10/2026', [35]],
    'water available' => ['air ada', [20, 35]],
    'quiet buzzer' => ['diam', [35]],
]);

test('the history table can be searched for readings without a soil temperature', function () {
    SensorData::factory()->create(['soil_percent' => 50]);
    SensorData::factory()->withoutSoilTemperature()->create(['soil_percent' => 70]);

    $table = $this->get(route('visitor', ['q' => 'ds18b20 error']))->assertOk()->viewData('table');

    expect(collect($table['data'])->pluck('soil_percent')->all())->toBe([70]);
});

test('the history table status filter matches the status shown in the table', function (string $status, array $expected) {
    SensorData::factory()->create(['soil_percent' => 60]);
    SensorData::factory()->soilDry()->create(['soil_percent' => 20]);
    SensorData::factory()->create(['soil_percent' => 25, 'is_soil_dry' => true]);
    SensorData::factory()->waterEmpty()->create(['soil_percent' => 70]);
    SensorData::factory()->create(['soil_percent' => 80, 'pump_status' => true, 'pump_command' => 'pump_on']);
    SensorData::factory()->withoutSoilTemperature()->create(['soil_percent' => 90, 'pump_command' => 'pump_off']);

    $table = $this->get(route('visitor', ['status' => $status]))->assertOk()->viewData('table');

    expect(collect($table['data'])->pluck('soil_percent')->sort()->values()->all())->toBe($expected)
        ->and($table['error'])->toBeNull();
})->with([
    'watering' => ['watering', [20]],
    'dry soil waiting for the pause' => ['waiting', [25]],
    'manual on' => ['manual_on', [80]],
    'manual off' => ['manual_off', [90]],
    'auto mode' => ['auto', [20, 25, 60, 70]],
    'buzzer alarm' => ['alarm', [20, 70]],
    'no soil temperature' => ['no_soil_temperature', [90]],
]);

test('the status select lists every status filter', function () {
    $this->get(route('visitor'))
        ->assertOk()
        ->assertSee('<optgroup label="Pompa">', false)
        ->assertSee('Kering - Tunggu Jeda')
        ->assertSee('Suhu tanah tidak terbaca');
});

test('search combines with the other filters and is kept in the pagination links', function () {
    SensorData::factory()->count(20)->soilDry()->create(['created_at' => '2026-10-21 10:00:00']);
    SensorData::factory()->soilDry()->create(['created_at' => '2026-10-19 10:00:00']);

    $table = $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['q' => 'kering', 'date_from' => '2026-10-21']))
        ->assertOk()
        ->assertSee('name="q"', false)
        ->viewData('table');

    expect($table['meta']['total'])->toBe(20)
        ->and($table['links']['next'])->toContain('q=kering')->toContain('date_from=2026-10-21');
});
