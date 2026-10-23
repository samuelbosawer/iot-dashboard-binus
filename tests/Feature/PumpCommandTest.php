<?php

use App\Enums\PumpCommand;
use App\Models\User;
use App\Services\Mqtt\MqttClientFactory;
use App\Services\Mqtt\PumpCommandPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpMqtt\Client\Contracts\MqttClient;
use PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException;

uses(RefreshDatabase::class);

test('a signed in user can send a pump command', function () {
    $this->mock(PumpCommandPublisher::class)
        ->shouldReceive('publish')->once()->with(PumpCommand::On);

    $this->actingAs(User::factory()->create())
        ->postJson(route('pump.command'), ['command' => 'pump_on'])
        ->assertOk()
        ->assertJson(['command' => 'pump_on']);
});

test('guests cannot control the pump', function () {
    $this->mock(PumpCommandPublisher::class)->shouldNotReceive('publish');

    $this->post(route('pump.command'), ['command' => 'pump_on'])->assertRedirect(route('login'));
});

test('unknown pump commands are rejected', function () {
    $this->mock(PumpCommandPublisher::class)->shouldNotReceive('publish');

    $this->actingAs(User::factory()->create())
        ->postJson(route('pump.command'), ['command' => 'reboot'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('command');
});

test('an unreachable broker is reported instead of crashing', function () {
    $this->mock(PumpCommandPublisher::class)
        ->shouldReceive('publish')->andThrow(new ConnectingToBrokerFailedException(1, 'timeout'));

    $this->actingAs(User::factory()->create())
        ->postJson(route('pump.command'), ['command' => 'auto'])
        ->assertServiceUnavailable();
});

test('the publisher sends the raw command to the pump topic without retaining it', function () {
    $client = Mockery::mock(MqttClient::class);
    $client->shouldReceive('publish')->once()->with('binus/garden/pump/cmd', 'pump_off', 0, false);
    $client->shouldReceive('disconnect')->once();

    $this->mock(MqttClientFactory::class)
        ->shouldReceive('connect')->with('publisher')->andReturn($client);

    app(PumpCommandPublisher::class)->publish(PumpCommand::Off);
});

test('the dashboard shows the pump controls and connects to the mqtt websocket', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('pump.command'))
        ->assertSee('data-pump-command="pump_on"', false)
        ->assertSee('mqtt.min.js')
        ->assertSee('wss:\/\/broker.hivemq.com:8884\/mqtt', false)
        ->assertDontSee('/api/sensor');
});
