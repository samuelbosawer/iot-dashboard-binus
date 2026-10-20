<?php

namespace App\Services\Mqtt;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Contracts\MqttClient as MqttClientContract;
use PhpMqtt\Client\MqttClient;

/**
 * Builds MQTT clients for the broker configured in config/mqtt.php.
 */
class MqttClientFactory
{
    /**
     * Create a client with a unique id, e.g. "taman-pintar-web-listener-1a2b3c".
     */
    public function make(string $role): MqttClientContract
    {
        $clientId = config('mqtt.client_id_prefix').'-'.$role.'-'.bin2hex(random_bytes(3));

        return new MqttClient(config('mqtt.host'), config('mqtt.port'), $clientId);
    }

    /**
     * Connection settings shared by the listener and the publisher.
     */
    public function settings(): ConnectionSettings
    {
        return (new ConnectionSettings)
            ->setUsername(config('mqtt.username') ?: null)
            ->setPassword(config('mqtt.password') ?: null)
            ->setUseTls(config('mqtt.tls'))
            ->setConnectTimeout(10)
            ->setKeepAliveInterval(10);
    }

    /**
     * Create a client and connect it with a clean session.
     */
    public function connect(string $role): MqttClientContract
    {
        $client = $this->make($role);
        $client->connect($this->settings(), true);

        return $client;
    }
}
