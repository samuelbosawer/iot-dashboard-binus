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
