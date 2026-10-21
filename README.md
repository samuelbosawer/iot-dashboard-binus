# Taman Pintar IoT

Monitoring tanaman dan penyiraman otomatis berbasis **ESP32 + MQTT**, dengan dashboard web **Laravel 12**.

ESP32 membaca kelembaban tanah, float switch tandon, dan suhu/kelembaban udara (DHT11), lalu mengirim datanya ke broker MQTT setiap 10 detik, dan langsung saat pompa, mode, atau tandon berubah. Website menampilkan data secara realtime, menyimpan riwayatnya, dan bisa mengirim perintah pompa balik ke ESP32.

- **Halaman publik** (`/`): data live, laporan, grafik, tabel, dan download CSV.
- **Prediksi** (`/prediksi`): tren kelembaban tanah dan rekomendasi perawatan.
- **Dashboard admin** (`/dashboard`): semua data sensor, kontrol pompa, dan kelola user.
- **PWA**: bisa di-install ke HP sebagai aplikasi, dengan menu tab di bawah layar.

---

## Arsitektur

```
                    ┌──────────────────────── broker.hivemq.com ────────────────────────┐
ESP32 ──publish──►  │  binus/garden/sensor/data                                         │ ──► php artisan mqtt:listen ──► database
                    │                                                                   │ ──► browser (WebSocket wss://…:8884) ──► tampilan live
ESP32 ◄─subscribe── │  binus/garden/pump/cmd   ◄── Laravel (tombol pompa di dashboard)  │
                    └───────────────────────────────────────────────────────────────────┘
```

| Bagian | Peran |
|---|---|
| ESP32 ([resources/esp32/](resources/esp32/iot_penyiram_binus/iot_penyiram_binus.ino)) | Membaca sensor, mengatur pompa, publish JSON, menerima perintah pompa |
| `php artisan mqtt:listen` | Subscribe ke topic sensor dan menyimpan setiap data ke tabel `sensor_data` |
| Browser | Subscribe langsung ke broker lewat WebSocket, jadi tampilan live tanpa polling |
| `POST /dashboard/pump` | Mengirim `auto` / `pump_on` / `pump_off` ke ESP32 (khusus user yang login) |
| `GET /api/sensor…` | Hanya-baca: tabel riwayat, rentang grafik, dan preview CSV dari database |

> ESP32 **tidak** lagi mengirim data lewat HTTP API. Semua data masuk lewat MQTT.

---

## Kebutuhan

- PHP 8.2+, Composer
- MySQL (atau database lain yang didukung Laravel)
- Node.js 20+ dan npm
- Arduino IDE dengan board **ESP32**

---

## Instalasi (lokal)

```bash
git clone <repo-url> iot-dashboard-binus
cd iot-dashboard-binus

composer install
cp .env.example .env
php artisan key:generate
```

Atur database di `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), lalu:

```bash
php artisan migrate --seed
npm install
npm run build
```

### Menjalankan

```bash
composer run dev
```

Perintah ini menjalankan sekaligus: web server, queue, Vite, dan **listener MQTT**. Buka `http://127.0.0.1:8000`.

Kalau ingin menjalankannya terpisah:

```bash
php artisan serve
php artisan mqtt:listen   # wajib berjalan supaya data tersimpan ke database
```

### Akun bawaan (dari seeder)

| Email | Password | Role |
|---|---|---|
| `admin@binus.ac.id` | `binus123` | Admin |
| `admin@iotbinus.test` | `password` | Admin |
| `support@iotbinus.test` | `password` | Support |

Ganti password ini sebelum dipakai di production. Hanya **Admin** yang bisa mengelola user.

---

## Konfigurasi MQTT

Semua nilai ada di [config/mqtt.php](config/mqtt.php) dan bisa diubah lewat `.env`:

| Variabel | Default | Keterangan |
|---|---|---|
| `MQTT_HOST` | `broker.hivemq.com` | Broker yang dipakai listener dan pengirim perintah pompa |
| `MQTT_PORT` | `1883` | `8883` jika memakai TLS |
| `MQTT_TLS` | `false` | |
| `MQTT_USERNAME` / `MQTT_PASSWORD` | kosong | Isi jika broker memakai login |
| `MQTT_WEBSOCKET_URL` | `wss://broker.hivemq.com:8884/mqtt` | Dipakai browser; halaman HTTPS wajib `wss://` |
| `MQTT_TOPIC_SENSOR` | `binus/garden/sensor/data` | Harus sama dengan `topic_publish` di firmware |
| `MQTT_TOPIC_PUMP` | `binus/garden/pump/cmd` | Harus sama dengan `topic_subscribe` di firmware |

Setelah mengubah `.env` di server, jalankan `php artisan config:clear` lalu restart `mqtt:listen`.

---

## ESP32 (firmware)

File: [resources/esp32/iot_penyiram_binus/iot_penyiram_binus.ino](resources/esp32/iot_penyiram_binus/iot_penyiram_binus.ino)

### Library Arduino

Install lewat **Library Manager**: `PubSubClient`, `ArduinoJson` (v7), `DHT sensor library`, `Adafruit SSD1306`, `Adafruit GFX`.

### Wiring

| Komponen | Pin ESP32 |
|---|---|
| Soil moisture (kapasitif) | D34 (ADC) |
| Float switch tandon | D15 (`INPUT_PULLUP`, LOW = air habis) |
| DHT11 | D4 |
| Relay pompa | D23 (aktif LOW) |
| Lampu indikator 1 | D25 (menyala saat pompa ON) |
| Lampu indikator 2 | D14 (menyala saat air habis) |
| Buzzer | D13 (bunyi 3× tiap 5 detik saat air habis **dan** tanah kering) |
| OLED SSD1306 | SDA 32, SCL 27, alamat `0x3C` |

### Yang perlu diatur sebelum upload

```cpp
const char* ssid = "NAMA_WIFI_ANDA";
const char* password = "PASSWORD_WIFI_ANDA";
```

Parameter lain yang sering disesuaikan:

| Konstanta | Arti |
|---|---|
| `SOIL_DRY`, `SOIL_WET` | Kalibrasi nilai raw sensor tanah (kering = 0%, basah = 100%) |
| `BATAS_KERING` | Di bawah persen ini tanah dianggap kering (default 35) |
| `DURASI_SIRAM` | Lama pompa menyala sekali siram (ms, default 8 detik) |
| `JEDA_SIRAM` | Jeda minimal antar penyiraman otomatis (ms, default 2 jam) |
| `FLOAT_HABIS` | Ubah ke `HIGH` jika status tandon terbaca terbalik |

> **Penting:** jangan hapus `client.setBufferSize(1024);`. Buffer default PubSubClient hanya 256 byte, sedangkan JSON sensor sekitar 300 byte. Tanpa baris ini, publish gagal tanpa pesan error dan data tidak pernah sampai ke broker.

### Logika pompa

| Perintah (`pump_command`) | Perilaku |
|---|---|
| `auto` (default saat ESP32 menyala) | Siram selama `DURASI_SIRAM` jika tanah < `BATAS_KERING`, air tersedia, dan sudah lewat `JEDA_SIRAM` |
| `pump_on` | Pompa menyala **terus** sampai dikirim `auto` atau `pump_off` |
| `pump_off` | Pompa mati, penyiraman otomatis berhenti |

Setelah tombol pompa ditekan, dashboard menampilkan *"menunggu konfirmasi ESP32…"* sampai ESP32 mengirim data dengan mode baru. Jika dalam 20 detik tidak ada konfirmasi, perangkat kemungkinan offline.

### Status yang ditampilkan di dashboard dan riwayat data

| Status | Kondisi |
|---|---|
| **Manual ON** / **Manual OFF** | Mode pompa diatur dari dashboard |
| **Menyiram** | Mode otomatis, pompa sedang menyala |
| **Air Habis** | Tandon kosong, pompa dikunci OFF |
| **Kering - Tunggu Jeda** | Tanah kering, tetapi penyiraman berikutnya menunggu jeda 2 jam |
| **Aman - Basah** | Tanah cukup basah, pompa standby |

Filter status di tabel riwayat (Aman - Basah / Tanah kering / Air habis) menyaring berdasarkan kondisi tanah dan tandon.

### Format pesan sensor

Dikirim ke `binus/garden/sensor/data` setiap 10 detik, **dan langsung** (maks. 1× per detik) saat pompa ON/OFF, mode pompa, atau status tandon berubah. Tanpa kiriman langsung ini, siram otomatis yang hanya 8 detik bisa terjadi di antara dua kiriman dan tidak tercatat, dan tombol pompa di dashboard baru terkonfirmasi sampai 10 detik kemudian.

```json
{
  "device_id": "ESP32-BAMER-01",
  "tanaman": "bawang_merah",
  "soil_percent": 26,
  "soil_raw": 2633,
  "temperature": 28,
  "humidity": 34.4,
  "water_raw": 0,
  "water_level_status": "EMPTY",
  "is_water_empty": true,
  "is_soil_dry": true,
  "pump_status": "OFF",
  "relay_d23": false,
  "pump_command": "auto"
}
```

Cara Laravel membaca pesan ini ([SensorReadingIngestor](app/Services/SensorReadingIngestor.php)):

- `relay_d23` dipakai sebagai status pompa yang sebenarnya.
- Suhu dan kelembaban `0` dianggap DHT gagal baca dan disimpan kosong.
- Lampu D25 = pompa ON, lampu D14 = air habis.
- Pesan yang bukan JSON atau nilainya di luar batas diabaikan (muncul sebagai peringatan di terminal `mqtt:listen`).

---

## Deploy ke server

1. Upload kode, lalu jalankan:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   npm ci && npm run build
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
2. Jalankan `mqtt:listen` terus-menerus dengan **Supervisor** (contoh `/etc/supervisor/conf.d/taman-pintar-mqtt.conf`):
   ```ini
   [program:taman-pintar-mqtt]
   command=php /path/ke/iot-dashboard-binus/artisan mqtt:listen
   autostart=true
   autorestart=true
   user=www-data
   redirect_stderr=true
   stdout_logfile=/path/ke/iot-dashboard-binus/storage/logs/mqtt.log
   ```
   ```bash
   sudo supervisorctl reread && sudo supervisorctl update
   ```
3. Situs harus **HTTPS** supaya PWA bisa di-install dan WebSocket `wss://` berjalan.
4. Setiap kali deploy ulang, restart listener: `sudo supervisorctl restart taman-pintar-mqtt`.

---

## Troubleshooting

| Gejala | Penyebab dan solusi |
|---|---|
| Serial Monitor `[WiFi] ✗ GAGAL KONEK` | SSID/password salah, atau WiFi 5 GHz (ESP32 hanya mendukung 2,4 GHz) |
| Serial Monitor `[MQTT] ✗ GAGAL rc=-2` | Port 1883 diblokir jaringan. Coba pakai hotspot HP |
| Serial Monitor `Publish: GAGAL` | `client.setBufferSize(1024);` belum ada di `setup()` |
| Dashboard live, tapi tabel/laporan kosong | `php artisan mqtt:listen` tidak berjalan |
| Status di dashboard "MQTT terputus" | Browser tidak bisa menjangkau `MQTT_WEBSOCKET_URL` (cek firewall atau koneksi internet) |
| Pompa tidak menyiram otomatis | Cek mode di dashboard. Jika `Manual OFF`, tekan **Otomatis** |
| Tandon terbaca HABIS padahal ada air | Ubah `#define FLOAT_HABIS LOW` menjadi `HIGH` |

Cek apakah ESP32 benar-benar mengirim data ke broker (tanpa menyimpan apa pun):

```bash
php artisan tinker --execute '$c = app(App\Services\Mqtt\MqttClientFactory::class)->connect("probe"); $c->subscribe(config("mqtt.topics.sensor"), fn ($t, $m) => print($m.PHP_EOL)); $c->loop(true);'
```

Tekan `Ctrl+C` untuk berhenti.

---

## Keamanan

- `broker.hivemq.com` adalah **broker publik**: siapa pun yang tahu nama topic bisa membaca data, mengirim data palsu, atau menyalakan pompa. Untuk pemakaian nyata, gunakan broker privat (misalnya HiveMQ Cloud) dengan username/password dan TLS, lalu isi `MQTT_*` di `.env` dan sesuaikan firmware.
- Jangan commit password WiFi asli di file `.ino`. Gunakan placeholder di repo dan isi hanya saat upload ke ESP32.

---

## Testing

```bash
php artisan test --compact
```

Format kode PHP:

```bash
vendor/bin/pint
```
