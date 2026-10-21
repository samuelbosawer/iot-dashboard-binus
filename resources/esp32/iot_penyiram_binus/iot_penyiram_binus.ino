/*
 * Smart Garden Bawang Merah - MQTT v2
 * Board  : ESP32 (Arduino IDE)
 * Library: PubSubClient, ArduinoJson (v7), DHT sensor library, Adafruit SSD1306, Adafruit GFX
 *
 * Komunikasi (tanpa HTTP API):
 *   Publish   binus/garden/sensor/data  -> JSON sensor tiap 10 detik
 *                                          + LANGSUNG saat pompa / mode / tandon berubah
 *                                          (disimpan Laravel lewat `php artisan mqtt:listen`,
 *                                           tampil live di dashboard lewat MQTT WebSocket)
 *   Subscribe binus/garden/pump/cmd     -> "auto" | "pump_on" | "pump_off" (tombol di dashboard)
 *
 * Kalibrasi & logika sensor disamakan dengan project lama (versi HTTP 3 API):
 *   - Water level float switch di D15 (sama dengan project lama)
 *   - Siklus baca sensor + logika pompa + OLED tiap 500 ms (dulu delay(500) di akhir loop),
 *     dibuat non-blocking supaya MQTT tetap responsif
 *
 * Kenapa publish saat berubah?
 *   Siram otomatis hanya 8 detik, sedangkan publish rutin tiap 10 detik. Tanpa publish saat
 *   berubah, pompa ON sering terjadi di antara dua publish sehingga tidak pernah tercatat di
 *   dashboard, dan tombol pompa di dashboard baru terkonfirmasi sampai 10 detik kemudian.
 *
 * Pin:
 *   Soil Moisture Capacitive : D34 (ADC)
 *   Water Level Float Switch : D15 (INPUT_PULLUP, LOW = air habis)
 *   DHT11                    : D4
 *   Pompa Air (relay)        : D23 (relay aktif LOW)
 *   Lampu Indikator 1        : D25 (pompa menyala)
 *   Lampu Indikator 2        : D14 (air tandon habis)
 *   Buzzer                   : D13
 *   OLED SSD1306             : SDA 32, SCL 27, alamat 0x3C
 *
 * Buzzer:
 *   Bunyi 3x pendek, diulang tiap 5 detik, selama AIR TANDON HABIS DAN TANAH KERING
 *   (tanaman butuh air tapi pompa tidak bisa menyiram).
 *   Ubah ALARM_BUTUH_KEDUANYA ke false kalau mau bunyi saat salah satu kondisi saja.
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <DHT.h>

const char* ssid = "AKUT SB";
const char* password = "samganteng#77";

// --- KONFIGURASI MQTT ---
const char* mqtt_server = "broker.hivemq.com";
const int mqtt_port = 1883;
const char* mqtt_user = "";
const char* mqtt_password = "";
const char* topic_publish = "binus/garden/sensor/data";
const char* topic_subscribe = "binus/garden/pump/cmd";

#define SDA_PIN 32
#define SCL_PIN 27
#define DHTPIN 4
#define DHTTYPE DHT11

// === SAMA DENGAN PROJECT LAMA ===
const int soilPin = 34, waterPin = 15, pumpPin = 23, lampu1 = 25, lampu2 = 14;

// === BUZZER ===
const int buzzerPin = 13;
#define BUZZER_PASIF false        // true kalau buzzer pasif (butuh frekuensi / tone)
#define BUZZER_AKTIF_HIGH true    // buzzer aktif: bunyi saat pin HIGH
const int BUZZER_FREQ = 2000;     // hanya dipakai buzzer pasif
const unsigned long BEEP_ON = 120, BEEP_OFF = 120, JEDA_ALARM_AIR = 5000;
#define ALARM_BUTUH_KEDUANYA true // true = air habis DAN tanah kering, false = salah satu

// === KALIBRASI SENSOR (SAMA DENGAN PROJECT LAMA) ===
const int SOIL_DRY = 3050, SOIL_WET = 1450, BATAS_KERING = 35;
const long DURASI_SIRAM = 8000, JEDA_SIRAM = 7200000;
#define FLOAT_HABIS LOW
#define RELAY_LOW true

const unsigned long INTERVAL_SENSOR = 500;    // sama dengan delay(500) project lama
const unsigned long INTERVAL_PUBLISH = 10000; // publish MQTT rutin tiap 10 detik
const unsigned long INTERVAL_MQTT_RETRY = 5000;
const unsigned long JEDA_PUBLISH_MIN = 1000;  // BARU: batas publish saat berubah (maks 1x per detik)

Adafruit_SSD1306 display(128, 64, &Wire, -1);
DHT dht(DHTPIN, DHTTYPE);
WiFiClient espClient;
PubSubClient client(espClient);

unsigned long lastSend = 0, lastSiram = 0, lastWiFiCheck = 0, lastPumpCheck = 0;
unsigned long lastSensor = 0, lastMqttRetry = 0;
bool sedangSiram = false;
unsigned long mulaiSiram = 0;
String pumpCommand = "auto";

// BARU: state terakhir yang sudah dikirim, untuk mendeteksi perubahan
bool perluPublish = true;
bool lastPompaTerkirim = false, lastHabisTerkirim = false;
String lastCommandTerkirim = "";

// state buzzer (non-blocking)
int beepSisa = 0;
bool beepNyala = false;
unsigned long beepTimer = 0, lastAlarmAir = 0;

void setPump(bool nyala) {
  digitalWrite(pumpPin, RELAY_LOW ? (nyala ? LOW : HIGH) : (nyala ? HIGH : LOW));
}

void setBuzzer(bool nyala) {
  if (BUZZER_PASIF) {
    if (nyala) tone(buzzerPin, BUZZER_FREQ); else noTone(buzzerPin);
  } else {
    digitalWrite(buzzerPin, BUZZER_AKTIF_HIGH ? (nyala ? HIGH : LOW) : (nyala ? LOW : HIGH));
  }
}

// Antrikan n bunyi pendek (maks 6 supaya tidak menumpuk)
void beep(int n) {
  beepSisa = min(beepSisa + n, 6);
}

// Dipanggil tiap loop, menjalankan antrian bunyi tanpa delay
void updateBuzzer() {
  unsigned long now = millis();
  if (beepNyala) {
    if (now - beepTimer >= BEEP_ON) { setBuzzer(false); beepNyala = false; beepTimer = now; }
  } else if (beepSisa > 0 && now - beepTimer >= BEEP_OFF) {
    setBuzzer(true); beepNyala = true; beepTimer = now; beepSisa--;
  }
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (unsigned int i = 0; i < length; i++) message += (char)payload[i];
  message.trim();

  Serial.println("\n========== PERINTAH MASUK ==========");
  Serial.printf("Topik  : %s\n", topic);
  Serial.printf("Pesan  : %s\n", message.c_str());
  Serial.printf("Waktu  : %lu detik\n", millis()/1000);
  Serial.println("====================================\n");

  // Project lama hanya menerima nilai perintah yang valid dari server
  if (message == "auto" || message == "pump_on" || message == "pump_off") {
    pumpCommand = message;
    lastPumpCheck = millis();
    perluPublish = true; // BARU: dashboard langsung dapat konfirmasi mode baru
  } else {
    Serial.println("[MQTT] Perintah tidak dikenal, diabaikan.");
  }
}

void reconnect() {
  if (client.connected()) return;
  if (millis() - lastMqttRetry < INTERVAL_MQTT_RETRY) return;
  lastMqttRetry = millis();
  if (WiFi.status() != WL_CONNECTED) return;

  Serial.println("\n[MQTT] Mencoba konek ke broker.hivemq.com ...");
  String clientId = "ESP32-BAMER-" + String(random(0xffff), HEX);

  if (client.connect(clientId.c_str(), mqtt_user, mqtt_password)) {
    Serial.println("[MQTT] ✓ BERHASIL TERKONEKSI!");
    Serial.printf("[MQTT] Subscribe ke: %s\n", topic_subscribe);
    client.subscribe(topic_subscribe);
    perluPublish = true; // BARU: kirim kondisi terkini setelah (re)connect
  } else {
    Serial.printf("[MQTT] ✗ GAGAL rc=%d\n", client.state());
  }
}

void ensureWiFi(){
  if (WiFi.status() == WL_CONNECTED) return;
  Serial.println("[WiFi] Terputus, reconnect...");
  WiFi.disconnect(true); delay(500);
  WiFi.begin(ssid, password);
  WiFi.setSleep(false);
}

// === SAMA DENGAN PROJECT LAMA: rata-rata 10 sampel ===
int bacaSoil() {
  long t = 0;
  for (int i = 0; i < 10; i++) { t += analogRead(soilPin); delay(10); }
  return t / 10;
}

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n\n");
  Serial.println("==========================================");
  Serial.println("  SMART GARDEN BAWANG MERAH - MQTT v2");
  Serial.println("==========================================");

  pinMode(pumpPin, OUTPUT);
  pinMode(lampu1, OUTPUT);
  pinMode(lampu2, OUTPUT);
  pinMode(waterPin, INPUT_PULLUP);
  pinMode(buzzerPin, OUTPUT);
  setBuzzer(false);

  setPump(false);
  digitalWrite(lampu1, LOW);
  digitalWrite(lampu2, LOW);

  Wire.begin(SDA_PIN, SCL_PIN);
  display.begin(SSD1306_SWITCHCAPVCC, 0x3C);
  dht.begin();

  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  WiFi.setTxPower(WIFI_POWER_19_5dBm);
  WiFi.begin(ssid, password);

  display.clearDisplay(); display.setCursor(0,0); display.setTextColor(1);
  display.println("Connecting WiFi..."); display.display();

  Serial.printf("[WiFi] Menghubungkan ke %s\n", ssid);
  int c = 0;
  while (WiFi.status() != WL_CONNECTED && c < 20) {
    delay(500);
    Serial.print(".");
    c++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("\n[WiFi] ✓ TERHUBUNG! IP: %s | RSSI: %d dBm\n", WiFi.localIP().toString().c_str(), WiFi.RSSI());
  } else {
    Serial.println("\n[WiFi] ✗ GAGAL KONEK (akan dicoba ulang otomatis)");
  }

  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(mqttCallback);
  client.setKeepAlive(60);
  // Default buffer PubSubClient hanya 256 byte, JSON sensor ~300 byte -> publish gagal diam-diam.
  client.setBufferSize(1024);

  // SAMA DENGAN PROJECT LAMA: boleh langsung siram saat start bila tanah kering
  lastSiram = millis() - JEDA_SIRAM;
  Serial.println("==========================================\n");
}

void loop() {
  if (millis() - lastWiFiCheck > 15000) { lastWiFiCheck = millis(); ensureWiFi(); }

  reconnect();
  client.loop();
  updateBuzzer();

  // Siklus sensor tiap 500 ms (pengganti delay(500) project lama, non-blocking)
  if (millis() - lastSensor < INTERVAL_SENSOR) return;
  lastSensor = millis();

  // ===== BACA SENSOR (SAMA DENGAN PROJECT LAMA) =====
  int raw = bacaSoil();
  int persen = constrain(map(raw, SOIL_DRY, SOIL_WET, 0, 100), 0, 100);
  int wRaw = digitalRead(waterPin);
  bool habis = (wRaw == FLOAT_HABIS);
  float hum = dht.readHumidity();
  float temp = dht.readTemperature();
  bool valid = !isnan(hum) && !isnan(temp);

  // ===== LOGIKA POMPA (SAMA DENGAN PROJECT LAMA) =====
  bool pompaOn = false;
  unsigned long now = millis();
  String stat;

  if (pumpCommand == "pump_on") {
    setPump(true); pompaOn = true; stat = "MANUAL ON"; sedangSiram = false;
  }
  else if (pumpCommand == "pump_off") {
    setPump(false); stat = "MANUAL OFF"; sedangSiram = false;
  }
  else {
    if (sedangSiram) {
      if (now - mulaiSiram >= DURASI_SIRAM) {
        setPump(false); sedangSiram = false; lastSiram = now; stat = "SELESAI";
      } else {
        setPump(true); pompaOn = true; stat = "AUTO SIRAM";
      }
    } else {
      if (habis) {
        stat = "AIR HABIS"; setPump(false);
      } else if (persen < BATAS_KERING && now - lastSiram >= JEDA_SIRAM) {
        setPump(true); sedangSiram = true; mulaiSiram = now; pompaOn = true; stat = "MULAI AUTO";
      } else {
        stat = persen < BATAS_KERING ? "TUNGGU" : "LEMBAB AMAN"; setPump(false);
      }
    }
  }

  digitalWrite(lampu1, pompaOn ? HIGH : LOW);
  digitalWrite(lampu2, habis ? HIGH : LOW);

  // ===== BUZZER =====
  bool kering = persen < BATAS_KERING;
  bool alarm = ALARM_BUTUH_KEDUANYA ? (habis && kering) : (habis || kering);
  if (alarm && now - lastAlarmAir >= JEDA_ALARM_AIR) {
    lastAlarmAir = now;
    beep(3);
  }

  // ===== OLED =====
  display.clearDisplay(); display.setCursor(0,0); display.setTextColor(1);
  display.printf("MQTT:%s %ddBm\n", client.connected() ? "ON" : "OFF", WiFi.RSSI());
  display.printf("Soil %d%% %s\n", persen, pumpCommand.c_str());
  if (valid) display.printf("T%.1f H%.0f\n", temp, hum);
  else display.println("DHT Error");
  display.printf("%s %s\n", habis ? "HABIS" : "OK", stat.c_str());
  display.display();

  // ===== PUBLISH MQTT =====
  // BARU: kirim langsung bila pompa / mode / tandon berubah, selain publish rutin tiap 10 detik
  bool berubah = perluPublish
    || pompaOn != lastPompaTerkirim
    || habis != lastHabisTerkirim
    || pumpCommand != lastCommandTerkirim;
  bool waktunyaRutin = millis() - lastSend > INTERVAL_PUBLISH;
  bool bolehKirimCepat = millis() - lastSend >= JEDA_PUBLISH_MIN;

  if (waktunyaRutin || (berubah && bolehKirimCepat)) {
    if (WiFi.status() != WL_CONNECTED) return;
    if (!client.connected()) return;
    lastSend = millis();

    // DATA MASTER - FIELD SAMA DENGAN PROJECT LAMA
    JsonDocument master;
    master["device_id"] = "ESP32-BAMER-01";
    master["tanaman"] = "bawang_merah";
    master["soil_percent"] = persen;
    master["soil_raw"] = raw;
    master["temperature"] = valid ? temp : 0;
    master["humidity"] = valid ? hum : 0;
    master["water_raw"] = wRaw;
    master["water_level_status"] = habis ? "EMPTY" : "OK";
    master["is_water_empty"] = habis;
    master["is_soil_dry"] = persen < BATAS_KERING;
    master["pump_status"] = pompaOn ? "ON" : "OFF";
    master["relay_d23"] = pompaOn;
    master["pump_command"] = pumpCommand;
    master["pump_command_local"] = pumpCommand;

    String payload;
    serializeJson(master, payload);
    bool terkirim = client.publish(topic_publish, payload.c_str());

    if (terkirim) {
      perluPublish = false;
      lastPompaTerkirim = pompaOn;
      lastHabisTerkirim = habis;
      lastCommandTerkirim = pumpCommand;
    }

    // SERIAL MONITOR RAPIH
    Serial.println("--------------------------------------------------");
    Serial.printf("| WAKTU: %lu detik | WiFi: %d dBm | MQTT: %s\n", millis()/1000, WiFi.RSSI(), client.connected() ? "OK" : "PUTUS");
    Serial.printf("| Publish: %s (%d byte)%s\n", terkirim ? "TERKIRIM" : "GAGAL", payload.length(), waktunyaRutin ? "" : " [perubahan]");
    Serial.println("--------------------------------------------------");
    Serial.printf("| Tanah : %3d%% (raw %4d) | %s\n", persen, raw, persen < BATAS_KERING ? "KERING" : "LEMBAB");
    if (valid) Serial.printf("| Udara : Suhu %.1f C | Hum %.0f%%\n", temp, hum);
    else Serial.println("| Udara : DHT ERROR");
    Serial.printf("| Air   : %s (pin %d) | Pompa : %s (%s)\n", habis ? "HABIS" : "OK", wRaw, pompaOn ? "NYALA" : "MATI", stat.c_str());
    Serial.printf("| Perintah: %s\n", pumpCommand.c_str());
    Serial.println("--------------------------------------------------");
    Serial.println(payload);
    Serial.println("--------------------------------------------------\n");
  }
}
