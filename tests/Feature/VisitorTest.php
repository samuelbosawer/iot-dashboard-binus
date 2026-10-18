<?php

use App\Models\SensorData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests can open the visitor page without logging in', function () {
    $this->get(route('visitor'))
        ->assertOk()
        ->assertSee('Data Sensor Live')
        ->assertSee('Laporan Umum')
        ->assertSee('Download Data CSV')
        ->assertSee('Suhu &amp; Kelembaban Udara (DHT D4)', false)
        ->assertSee('Belum ada data sensor pada periode ini.');
});

test('the visitor report summarises readings of the chosen period', function () {
    $this->travelTo(now()->setTime(12, 0));

    SensorData::factory()->create(['soil_percent' => 60, 'temperature' => 28, 'humidity' => 60]);
    SensorData::factory()->soilDry()->create(['soil_percent' => 20, 'temperature' => 32, 'humidity' => 50]);
    SensorData::factory()->waterEmpty()->withoutDht()->create(['soil_percent' => 40]);
    SensorData::factory()->create(['soil_percent' => 90, 'created_at' => now()->subDays(10)]);

    $response = $this->get(route('visitor', ['days' => 7]))->assertOk();

    expect($response->viewData('summary'))->toMatchArray([
        'total' => 3,
        'avg_soil' => 40.0,
        'avg_temperature' => 30.0,
        'min_temperature' => 28.0,
        'max_temperature' => 32.0,
        'avg_humidity' => 55.0,
        'pump_percent' => 33,
        'ok' => 1,
        'dry' => 1,
        'empty' => 1,
    ]);

    $daily = $response->viewData('daily');

    expect($daily)->toHaveCount(7)
        ->and(end($daily))->toMatchArray(['date' => now()->format('Y-m-d'), 'total' => 3, 'avg_temperature' => 30.0])
        ->and($daily[0]['total'])->toBe(0);

    $response->assertSee('Suhu udara normal, rata-rata 30,0 °C', false)
        ->assertSee('Air tandon sempat habis');
});

test('the visitor report rejects an unknown period', function () {
    $this->get(route('visitor', ['days' => 3]))->assertSessionHasErrors('days');
});

test('guests can download the csv from the visitor page', function () {
    SensorData::factory()->create(['soil_percent' => 55, 'temperature' => 27.5, 'humidity' => 70]);

    $response = $this->get(route('visitor.export', ['datasets' => ['soil', 'climate']]))
        ->assertOk()
        ->assertDownload();

    $rows = csvRows($response->streamedContent());

    expect($rows[0])->toBe(['id', 'waktu', 'soil_percent', 'soil_raw', 'tanah_kering', 'suhu_c', 'kelembaban_udara'])
        ->and($rows[1][2])->toBe('55')
        ->and(array_slice($rows[1], 5))->toBe(['27.5', '70']);
});

test('visitor csv downloads are rate limited', function () {
    foreach (range(1, 10) as $attempt) {
        $this->get(route('visitor.export', ['datasets' => ['soil']]))->assertOk();
    }

    $this->get(route('visitor.export', ['datasets' => ['soil']]))->assertTooManyRequests();
});

test('the admin dashboard shows the dht section and links to the visitor page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Suhu Udara')
        ->assertSee('Grafik Suhu &amp; Kelembaban Udara', false)
        ->assertSee(route('visitor'));
});
