<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendPumpCommandRequest;
use App\Services\Mqtt\PumpCommandPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use PhpMqtt\Client\Exceptions\MqttClientException;

class PumpCommandController extends Controller
{
    /**
     * Publish a pump command (auto / pump_on / pump_off) to the ESP32 over MQTT.
     */
    public function __invoke(SendPumpCommandRequest $request, PumpCommandPublisher $publisher): JsonResponse|RedirectResponse
    {
        $command = $request->command();

        try {
            $publisher->publish($command);
        } catch (MqttClientException $exception) {
            report($exception);
            $message = 'Gagal mengirim perintah ke broker MQTT. Coba lagi.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : back()->withErrors(['command' => $message]);
        }

        $message = 'Perintah "'.$command->label().'" dikirim ke ESP32.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'command' => $command->value])
            : back()->with('status', $message);
    }
}
