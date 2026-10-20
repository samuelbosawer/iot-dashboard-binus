<?php

namespace App\Services\Mqtt;

use App\Enums\PumpCommand;

/**
 * Sends a pump command to the ESP32 over MQTT.
 */
class PumpCommandPublisher
{
    public function __construct(private MqttClientFactory $clients) {}

    /**
     * Publish the command once, not retained: a retained "pump_on" would switch the
     * pump on again every time the ESP32 reconnects.
     */
    public function publish(PumpCommand $command): void
    {
        $client = $this->clients->connect('publisher');

        try {
            $client->publish(config('mqtt.topics.pump'), $command->value, 0, false);
        } finally {
            $client->disconnect();
        }
    }
}
