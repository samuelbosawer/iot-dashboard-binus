/*
 * IoT Penyiram Tanaman Binus - Monitoring Soil & Water
 * Board  : ESP32 (Arduino IDE)
 * Library: ArduinoJson (v7), Adafruit SSD1306, Adafruit GFX
 *
 * Pin:
 *   Soil Moisture Capacitive : D34 (ADC)
 *   Water Level Float Switch : D33 (digital, INPUT_PULLUP)
 *                              kabel 1 -> D33, kabel 2 -> GND (tidak perlu resistor)
 *   Pompa Air                : D23
 *   Lampu Indikator 1        : D25 (tanah kering / sedang menyiram)
 *   Lampu Indikator 2        : D14 (air tandon habis)
 *   Buzzer                   : D13
 *   OLED SSD1306             : SDA 32, SCL 27, alamat 0x3C
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// ================= KONFIGURASI =================
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_ANDA";

const char* SERVER_URL = "https://iotbinus.projectbos.web.id/api/sensor";
const char* API_KEY    = "binus-iot-2025";

const unsigned long SEND_INTERVAL_MS = 3000;

// Kalibrasi sensor (sesuaikan dengan sensor Anda)
const int SOIL_RAW_DRY = 3200;        // nilai raw saat sensor di udara (0%)
const int SOIL_RAW_WET = 1300;        // nilai raw saat sensor di air (100%)
const int SOIL_DRY_ON_PERCENT  = 30;  // di bawah ini dianggap kering -> siram
const int SOIL_DRY_OFF_PERCENT = 45;  // di atas ini pompa berhenti (histeresis)

// Float switch: true jika kontak TERTUTUP (D33 = LOW) saat pelampung NAIK / air ada.
// Jika di dashboard terbalik (air ada tapi tertulis HABIS), ubah ke false
// atau balik posisi pelampung pada batangnya.
const bool FLOAT_CLOSED_WHEN_WATER = true;
const unsigned long FLOAT_DEBOUNCE_MS = 1000;  // status harus stabil 1 detik (air bergelombang)
// ===============================================

// Pin
const int PIN_SOIL   = 34;
const int PIN_WATER  = 33;
const int PIN_PUMP   = 23;
const int PIN_LAMPU1 = 25;
const int PIN_LAMPU2 = 14;
const int PIN_BUZZER = 13;
const int PIN_SDA    = 32;
const int PIN_SCL    = 27;

// OLED
const int SCREEN_WIDTH  = 128;
const int SCREEN_HEIGHT = 64;
const uint8_t OLED_ADDRESS = 0x3C;
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
bool oledReady = false;

unsigned long lastSendMs = 0;
bool pumpOn = false;
int lastHttpCode = 0;

bool waterEmptyStable = true;       // anggap habis sampai float switch terbaca stabil
bool waterEmptyCandidate = true;
unsigned long waterCandidateSinceMs = 0;

// Baca float switch dengan debounce agar gelombang air tidak membuat status berkedip.
bool readWaterEmpty() {
  bool contactClosed = digitalRead(PIN_WATER) == LOW;
  bool waterPresent  = FLOAT_CLOSED_WHEN_WATER ? contactClosed : !contactClosed;
  bool emptyNow      = !waterPresent;

  if (emptyNow != waterEmptyCandidate) {
    waterEmptyCandidate = emptyNow;
    waterCandidateSinceMs = millis();
  } else if (emptyNow != waterEmptyStable && millis() - waterCandidateSinceMs >= FLOAT_DEBOUNCE_MS) {
    waterEmptyStable = emptyNow;
  }

  return waterEmptyStable;
}

int readAverage(int pin, int samples = 10) {
  long total = 0;
  for (int i = 0; i < samples; i++) {
    total += analogRead(pin);
    delay(5);
  }
  return total / samples;
}

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan WiFi");

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 15000) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("\nWiFi terhubung, IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi gagal, akan dicoba lagi.");
  }
}

void drawOled(int soilPercent, bool waterEmpty, bool soilDry, bool pump) {
  if (!oledReady) return;

  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(1);

  display.setCursor(0, 0);
  display.print("IoT Penyiram Binus");

  display.setCursor(0, 14);
  display.printf("Tanah : %d %%", soilPercent);

  display.setCursor(0, 24);
  display.printf("Air   : %s", waterEmpty ? "HABIS" : "ADA");

  display.setCursor(0, 34);
  display.printf("Pompa : %s", pump ? "ON" : "OFF");

  display.setCursor(0, 44);
  if (waterEmpty) {
    display.print("STATUS: AIR HABIS!");
  } else if (soilDry) {
    display.print("STATUS: TANAH KERING");
  } else {
    display.print("STATUS: AMAN");
  }

  display.setCursor(0, 55);
  display.print(WiFi.status() == WL_CONNECTED ? "WiFi OK " : "WiFi -- ");
  display.printf("HTTP:%d", lastHttpCode);

  display.display();
}

void sendToServer(int soilPercent, int soilRaw, bool waterEmpty,
                  bool soilDry, bool pump, bool lampu1, bool lampu2) {
  if (WiFi.status() != WL_CONNECTED) {
    lastHttpCode = 0;
    return;
  }

  JsonDocument doc;
  doc["soil_percent"]   = soilPercent;
  doc["soil_raw"]       = soilRaw;
  doc["is_water_empty"] = waterEmpty;
  doc["is_soil_dry"]    = soilDry;
  doc["pump_status"]    = pump;
  doc["lampu1_d25"]     = lampu1;
  doc["lampu2_d14"]     = lampu2;

  String payload;
  serializeJson(doc, payload);

  WiFiClientSecure client;
  client.setInsecure();  // lewati verifikasi sertifikat (sederhana untuk praktikum)

  HTTPClient http;
  http.setTimeout(8000);

  if (http.begin(client, SERVER_URL)) {
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("x-api-key", API_KEY);

    lastHttpCode = http.POST(payload);
    Serial.printf("POST %s -> HTTP %d\n", payload.c_str(), lastHttpCode);

    if (lastHttpCode <= 0) {
      Serial.println(http.errorToString(lastHttpCode));
    }
    http.end();
  }
}

void setup() {
  Serial.begin(115200);

  pinMode(PIN_PUMP, OUTPUT);
  pinMode(PIN_LAMPU1, OUTPUT);
  pinMode(PIN_LAMPU2, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  pinMode(PIN_WATER, INPUT_PULLUP);  // float switch ke GND, pull-up internal
  digitalWrite(PIN_PUMP, LOW);
  digitalWrite(PIN_LAMPU1, LOW);
  digitalWrite(PIN_LAMPU2, LOW);
  digitalWrite(PIN_BUZZER, LOW);

  analogReadResolution(12);  // 0 - 4095

  Wire.begin(PIN_SDA, PIN_SCL);
  oledReady = display.begin(SSD1306_SWITCHCAPVCC, OLED_ADDRESS);
  if (!oledReady) {
    Serial.println("OLED tidak ditemukan (0x3C), lanjut tanpa OLED.");
  } else {
    display.clearDisplay();
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(0, 0);
    display.println("IoT Penyiram Binus");
    display.println("Menghubungkan WiFi..");
    display.display();
  }

  connectWiFi();
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  int soilRaw   = readAverage(PIN_SOIL);
  int soilPercent = constrain(map(soilRaw, SOIL_RAW_DRY, SOIL_RAW_WET, 0, 100), 0, 100);

  bool waterEmpty = readWaterEmpty();
  bool soilDry    = soilPercent < SOIL_DRY_ON_PERCENT;

  // Pompa menyala saat tanah kering & air ada; mati saat tanah cukup basah / air habis
  if (waterEmpty) {
    pumpOn = false;
  } else if (soilDry) {
    pumpOn = true;
  } else if (soilPercent >= SOIL_DRY_OFF_PERCENT) {
    pumpOn = false;
  }

  bool lampu1 = soilDry || pumpOn;  // D25: tanah kering / sedang menyiram
  bool lampu2 = waterEmpty;         // D14: air tandon habis

  digitalWrite(PIN_PUMP, pumpOn ? HIGH : LOW);
  digitalWrite(PIN_LAMPU1, lampu1 ? HIGH : LOW);
  digitalWrite(PIN_LAMPU2, lampu2 ? HIGH : LOW);

  // Buzzer berbunyi putus-putus saat air habis
  digitalWrite(PIN_BUZZER, (waterEmpty && (millis() / 500) % 2 == 0) ? HIGH : LOW);

  if (millis() - lastSendMs >= SEND_INTERVAL_MS) {
    lastSendMs = millis();
    sendToServer(soilPercent, soilRaw, waterEmpty, soilDry, pumpOn, lampu1, lampu2);
  }

  drawOled(soilPercent, waterEmpty, soilDry, pumpOn);
  delay(100);
}
