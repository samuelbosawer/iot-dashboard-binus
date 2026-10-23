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
        ->assertSee('Suhu Tanah')
        ->assertSee('Alarm Buzzer')
        ->assertDontSee('DHT')
        ->assertDontSee('Kelembaban Udara')
        ->assertDontSee('Pin D')
        ->assertSee('Belum ada data sensor pada periode ini.');
});

test('the visitor report summarises readings of the chosen period', function () {
    $this->travelTo(now()->setTime(12, 0));

    SensorData::factory()->create(['soil_percent' => 60, 'soil_temperature' => 26]);
    SensorData::factory()->soilDry()->create(['soil_percent' => 20, 'soil_temperature' => 30]);
    SensorData::factory()->waterEmpty()->withoutSoilTemperature()->create(['soil_percent' => 40]);
    SensorData::factory()->create(['soil_percent' => 90, 'created_at' => now()->subDays(10)]);

    $response = $this->get(route('visitor', ['days' => 7]))->assertOk();

    expect($response->viewData('summary'))->toMatchArray([
        'total' => 3,
        'avg_soil' => 40.0,
        'avg_soil_temperature' => 28.0,
        'min_soil_temperature' => 26.0,
        'max_soil_temperature' => 30.0,
        'pump_percent' => 33,
        'buzzer_percent' => 67,
        'ok' => 1,
        'dry' => 1,
        'empty' => 1,
    ]);

    $daily = $response->viewData('daily');

    expect($daily)->toHaveCount(7)
        ->and(end($daily))->toMatchArray(['date' => now()->format('Y-m-d'), 'total' => 3, 'avg_soil_temperature' => 28.0])
        ->and($daily[0]['total'])->toBe(0);

    $response->assertSee('Suhu tanah ideal, rata-rata 28,0 °C', false)
        ->assertSee('Alarm buzzer berbunyi pada 67% pembacaan')
        ->assertSee('Air tandon sempat habis');
});

test('the visitor report rejects an unknown period', function () {
    $this->get(route('visitor', ['days' => 3]))->assertSessionHasErrors('days');
});

test('guests can download the csv from the visitor page', function () {
    SensorData::factory()->create(['soil_percent' => 55, 'soil_temperature' => 27.5]);

    $response = $this->get(route('visitor.export', ['datasets' => ['soil', 'soil_temperature']]))
        ->assertOk()
        ->assertDownload();

    $rows = csvRows($response->streamedContent());

    expect($rows[0])->toBe(['id', 'waktu', 'soil_percent', 'soil_raw', 'tanah_kering', 'suhu_tanah_c'])
        ->and($rows[1][2])->toBe('55')
        ->and($rows[1][5])->toBe('27.5');
});

test('visitor csv downloads are rate limited', function () {
    foreach (range(1, 10) as $attempt) {
        $this->get(route('visitor.export', ['datasets' => ['soil']]))->assertOk();
    }

    $this->get(route('visitor.export', ['datasets' => ['soil']]))->assertTooManyRequests();
});

test('the admin dashboard shows soil temperature and buzzer but hides the dht sensor', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Suhu Tanah')
        ->assertSee('Alarm Buzzer')
        ->assertSee('Grafik Suhu Tanah &amp; Alarm Buzzer', false)
        ->assertDontSee('DHT')
        ->assertDontSee('Kelembaban Udara')
        ->assertSee(route('visitor'));
});

test('the bottom tab bar links to the visitor sections and marks the prediction page', function () {
    $this->get(route('visitor'))
        ->assertOk()
        ->assertSee('href="#live"', false)
        ->assertSee('data-section="live"', false)
        ->assertSee('v-table-cards');

    $this->get(route('prediction'))
        ->assertOk()
        ->assertSee('href="'.route('visitor').'#tabel"', false)
        ->assertSee('data-active aria-current="page"', false);
});
