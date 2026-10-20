<?php

use App\Models\SensorData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests can open the prediction menu from the visitor site', function () {
    SensorData::factory()->count(2)->create();
    SensorData::factory()->waterEmpty()->create();

    $response = $this->get(route('prediction'))
        ->assertOk()
        ->assertSee('Prediksi Kelembaban Tanah')
        ->assertSee('Prediksi Air Tandon Habis')
        ->assertSee('Mengumpulkan data');

    expect($response->viewData('dataset'))->toMatchArray(['total' => 3, 'days' => 1, 'empty' => 1]);
});

test('the admin dashboard has a prediction menu that requires login', function () {
    $this->get(route('dashboard.prediction'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard.prediction'))
        ->assertOk()
        ->assertSee('Prediksi Sensor Soil &amp; Water', false)
        ->assertSee(route('dashboard.prediction'));
});

test('the visitor page links to the prediction menu', function () {
    $this->get(route('visitor'))->assertOk()->assertSee(route('prediction'));
});

test('the prediction chart uses real hourly data and projects the soil trend', function () {
    $this->travelTo(now()->setTime(12, 30));

    foreach ([5, 4, 3, 2, 1, 0] as $index => $hoursAgo) {
        SensorData::factory()->create(['soil_percent' => 70 - $index * 5, 'created_at' => now()->subHours($hoursAgo)]);
    }

    $response = $this->get(route('prediction'))->assertOk();
    $trend = $response->viewData('trend');
    $hourly = $response->viewData('hourly');

    expect($hourly)->toHaveCount(48)
        ->and(end($hourly)['avg_soil'])->toBe(45.0)
        ->and($trend['available'])->toBeTrue()
        ->and($trend['slope'])->toBe(-5.0)
        ->and($trend['hours_until_dry'])->toBe(2.0)
        ->and($trend['projection'])->toHaveCount(6);

    $response->assertSee('Tanah segera mengering')->assertSee('prediction-chart');
});

test('recommendations warn when the tank is empty and dht data is missing', function () {
    SensorData::factory()->waterEmpty()->withoutDht()->create();

    $titles = collect($this->get(route('prediction'))->viewData('recommendations'))->pluck('title');

    expect($titles)->toContain('Isi tandon sekarang', 'Data suhu belum masuk', 'Lanjutkan pengumpulan data')
        ->not->toContain('Kondisi stabil');
});
