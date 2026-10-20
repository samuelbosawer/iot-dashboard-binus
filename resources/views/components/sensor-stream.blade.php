{{-- Live ESP32 readings over MQTT (WebSocket). Exposes window.SensorStream.connect({ onReading, onStatus }). --}}
@php
    $streamConfig = ['url' => config('mqtt.websocket_url'), 'topic' => config('mqtt.topics.sensor')];
@endphp

<script src="https://cdnjs.cloudflare.com/ajax/libs/mqtt/5.10.1/mqtt.min.js"></script>
<script>
    window.SensorStream = (() => {
        const CONFIG = @json($streamConfig);
        const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const pad = (value) => String(value).padStart(2, '0');
        const toBoolean = (value) => value === true || value === 1 || value === '1' || value === 'ON' || value === 'true';

        /**
         * Same mapping as App\Services\SensorReadingIngestor, so a live reading looks like a stored one.
         */
        const normalize = (payload, receivedAt = new Date()) => {
            const hasDht = !(Number(payload.temperature) === 0 && Number(payload.humidity) === 0)
                && payload.temperature !== null && payload.temperature !== undefined;
            const pump = 'relay_d23' in payload ? toBoolean(payload.relay_d23) : toBoolean(payload.pump_status);
            const isWaterEmpty = toBoolean(payload.is_water_empty);
            const time = pad(receivedAt.getHours()) + ':' + pad(receivedAt.getMinutes()) + ':' + pad(receivedAt.getSeconds());

            return {
                id: null,
                soil_percent: Number(payload.soil_percent),
                soil_raw: Number(payload.soil_raw),
                water_raw: payload.water_raw ?? null,
                temperature: hasDht ? Number(payload.temperature) : null,
                humidity: hasDht ? Number(payload.humidity) : null,
                is_water_empty: isWaterEmpty,
                is_soil_dry: toBoolean(payload.is_soil_dry),
                pump_status: pump,
                pump_command: payload.pump_command ?? null,
                lampu1_d25: 'lampu1_d25' in payload ? toBoolean(payload.lampu1_d25) : pump,
                lampu2_d14: 'lampu2_d14' in payload ? toBoolean(payload.lampu2_d14) : isWaterEmpty,
                created_at: receivedAt.toISOString(),
                created_at_label: pad(receivedAt.getDate()) + ' ' + MONTHS[receivedAt.getMonth()] + ' ' + receivedAt.getFullYear() + ' ' + time,
                time_label: time,
            };
        };

        /**
         * handlers.onReading(row) gets each live reading; handlers.onStatus(state) gets 'connecting', 'connected' or 'offline'.
         */
        const connect = ({ onReading, onStatus = () => {} }) => {
            if (!window.mqtt) {
                onStatus('offline');
                return null;
            }

            onStatus('connecting');
            const client = window.mqtt.connect(CONFIG.url, {
                clientId: 'taman-pintar-browser-' + Math.random().toString(16).slice(2, 10),
                clean: true,
                reconnectPeriod: 5000,
                connectTimeout: 10000,
            });

            client.on('connect', () => {
                onStatus('connected');
                client.subscribe(CONFIG.topic);
            });
            client.on('reconnect', () => onStatus('connecting'));
            client.on('offline', () => onStatus('offline'));
            client.on('error', () => onStatus('offline'));
            client.on('message', (topic, message) => {
                try {
                    const payload = JSON.parse(message.toString());
                    if (Number.isFinite(Number(payload.soil_percent))) {
                        onReading(normalize(payload));
                    }
                } catch (error) {
                    // Ignore messages that are not sensor JSON.
                }
            });

            return client;
        };

        return { connect, normalize };
    })();
</script>
