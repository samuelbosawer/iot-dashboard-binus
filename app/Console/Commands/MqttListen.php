<?php

namespace App\Console\Commands;

use App\Services\Mqtt\MqttClientFactory;
use App\Services\SensorReadingIngestor;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use JsonException;
use PhpMqtt\Client\Contracts\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;

class MqttListen extends Command
{
    private const RECONNECT_DELAY_SECONDS = 5;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to the ESP32 sensor topic and store every reading (keep this running, e.g. with Supervisor)';

    private bool $shouldStop = false;

    private ?MqttClient $client = null;

    /**
     * Execute the console command.
     */
    public function handle(MqttClientFactory $clients, SensorReadingIngestor $ingestor): int
    {
        // Signals are only available with the pcntl extension (not on Windows).
        if (defined('SIGINT')) {
            $this->trap([SIGINT, SIGTERM], function (): void {
                $this->shouldStop = true;
                $this->client?->interrupt();
            });
        }

        $topic = config('mqtt.topics.sensor');
        $broker = config('mqtt.host').':'.config('mqtt.port').(config('mqtt.tls') ? ' (TLS)' : '');

        // An old `php artisan config:cache` made before config/mqtt.php existed leaves these empty.
        if (blank(config('mqtt.host')) || blank($topic)) {
            $this->error('Konfigurasi MQTT kosong. Jalankan `php artisan config:clear` (atau config:cache ulang) lalu coba lagi.');

            return self::FAILURE;
        }

        $this->line('Menghubungkan ke '.$broker.' …');

        while (! $this->shouldStop) {
            try {
                $this->client = $clients->connect('listener');
                $this->info('Terhubung ke '.$broker.', subscribe '.$topic);

                $this->client->subscribe($topic, fn (string $topic, string $message) => $this->store($ingestor, $message));
                $this->client->loop(true);
                $this->client->disconnect();
            } catch (MqttClientException $exception) {
                $this->error('MQTT ke '.$broker.' gagal: '.$exception->getMessage().'. Mencoba lagi dalam '.self::RECONNECT_DELAY_SECONDS.' detik…');
                sleep(self::RECONNECT_DELAY_SECONDS);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Store one message; a bad message is reported and skipped so the listener keeps running.
     */
    private function store(SensorReadingIngestor $ingestor, string $message): void
    {
        try {
            $reading = $ingestor->ingest($message);
            $this->line(sprintf('[%s] #%d tanah %d%% · pompa %s', $reading->created_at->format('H:i:s'), $reading->id, $reading->soil_percent, $reading->pump_status ? 'ON' : 'OFF'));
        } catch (JsonException|ValidationException $exception) {
            $this->warn('Pesan diabaikan: '.$exception->getMessage().' · '.$message);
        }
    }
}
