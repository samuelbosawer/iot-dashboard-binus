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

        const PUMP_STATES = {
            manual_on: { label: 'Manual ON', text: 'Pompa dinyalakan manual dari dashboard dan terus menyala sampai diganti.', icon: 'fa-hand-pointer', badge: 'bg-emerald-100 text-emerald-700', solid: 'bg-emerald-500 text-white' },
            manual_on_empty: { label: 'Manual ON', text: 'Pompa menyala manual padahal air tandon habis. Pompa bisa rusak, matikan segera!', icon: 'fa-triangle-exclamation', badge: 'bg-red-100 text-red-700', solid: 'bg-red-500 text-white' },
            manual_off: { label: 'Manual OFF', text: 'Pompa dimatikan manual, penyiraman otomatis tidak berjalan.', icon: 'fa-ban', badge: 'bg-slate-200 text-slate-700', solid: 'bg-slate-500 text-white' },
            watering: { label: 'Menyiram', text: 'Penyiraman otomatis sedang berjalan (±8 detik).', icon: 'fa-faucet-drip', badge: 'bg-emerald-100 text-emerald-700', solid: 'bg-emerald-500 text-white' },
            empty: { label: 'Air Habis', text: 'Air tandon habis, pompa dikunci OFF sampai tandon diisi.', icon: 'fa-triangle-exclamation', badge: 'bg-red-100 text-red-700', solid: 'bg-red-500 text-white' },
            waiting: { label: 'Kering - Tunggu Jeda', text: 'Tanah kering, menunggu jadwal siram berikutnya (maks. sekali tiap 2 jam).', icon: 'fa-hourglass-half', badge: 'bg-amber-100 text-amber-700', solid: 'bg-amber-400 text-white' },
            ok: { label: 'Aman - Basah', text: 'Tanah cukup basah dan air tersedia, pompa standby.', icon: 'fa-circle-check', badge: 'bg-sky-100 text-sky-700', solid: 'bg-emerald-500 text-white' },
        };

        /**
         * What the pump is really doing. A dry soil alone does not mean watering: auto mode waters
         * 8 s at most once every 2 hours. Mirrors App\Models\SensorData::statusLabel().
         */
        const pumpState = (d) => {
            const key = d.pump_command === 'pump_on' ? (d.is_water_empty ? 'manual_on_empty' : 'manual_on')
                : d.pump_command === 'pump_off' ? 'manual_off'
                : d.pump_status ? 'watering'
                : d.is_water_empty ? 'empty'
                : d.is_soil_dry ? 'waiting'
                : 'ok';

            return { key, ...PUMP_STATES[key] };
        };

        const PUMP_MODES = { auto: 'Otomatis', pump_on: 'Manual ON', pump_off: 'Manual OFF' };
        const pumpModeLabel = (command) => PUMP_MODES[command] ?? command ?? '--';

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

        return { connect, normalize, pumpState, pumpModeLabel };
    })();
</script>
