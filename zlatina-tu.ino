#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "Test";
const char* password = "12345678";

const char* scheduleUrl = "http://192.168.193.122/medicine_website/get_schedule.php";
const char* confirmUrl  = "http://192.168.193.122/medicine_website/mark_taken_api.php";


// Пинове
const int buzzerPin = 21;
const int redLedPin = 33;
const int greenLedPin = 32;
const int buttonPin = 27;

// Глобални състояния
int scheduledHour = -1;
int scheduledMinute = -1;
int scheduledId = -1;
bool alarmActive = false;
int lastTriggeredMinute = -1;
unsigned long alarmStart = 0;
unsigned long maxAlarmDuration = 60000; // 60 секунди

// Предварителни декларации
void fetchSchedule();
void triggerAlarm();
void resolveAlarm();
void sendConfirmation(int id);

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi Connected!");

  pinMode(buzzerPin, OUTPUT);
  pinMode(redLedPin, OUTPUT);
  pinMode(greenLedPin, OUTPUT);
  pinMode(buttonPin, INPUT_PULLUP);

  digitalWrite(buzzerPin, LOW);
  digitalWrite(redLedPin, LOW);
  digitalWrite(greenLedPin, LOW);

  fetchSchedule(); // Зареждане на първото лекарство
}

void loop() {
  // Фиктивно време за тест – променяй тези 2 стойности според нуждите
  int fakeHour = 22;
  int fakeMinute = 37;

  Serial.print("Current time: ");
  Serial.print(fakeHour);
  Serial.print(":");
  Serial.println(fakeMinute);

  static int lastMinuteChecked = -1;
  if (fakeMinute != lastMinuteChecked) {
    lastTriggeredMinute = -1;
    lastMinuteChecked = fakeMinute;
  }

  if (!alarmActive &&
      scheduledHour == fakeHour &&
      scheduledMinute == fakeMinute &&
      lastTriggeredMinute != fakeMinute) {

    triggerAlarm();
    lastTriggeredMinute = fakeMinute;
  }

  if (alarmActive && digitalRead(buttonPin) == LOW) {
    resolveAlarm();
    sendConfirmation(scheduledId);
    delay(2000);
    fetchSchedule();
  }

  if (alarmActive && millis() - alarmStart > maxAlarmDuration) {
    Serial.println("ALARM TIMEOUT");
    resolveAlarm();
  }

  delay(1000);
}

void triggerAlarm() {
  if (!alarmActive) {
    digitalWrite(buzzerPin, HIGH);
    digitalWrite(redLedPin, HIGH);
    alarmStart = millis();
    alarmActive = true;
    Serial.println("ALARM ON");
  }
}

void resolveAlarm() {
  digitalWrite(buzzerPin, LOW);
  digitalWrite(redLedPin, LOW);
  digitalWrite(greenLedPin, HIGH);
  Serial.println("ALARM RESOLVED");
  delay(2000);
  digitalWrite(greenLedPin, LOW);
  alarmActive = false;
}

void sendConfirmation(int id) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(confirmUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String postData = "id=" + String(id);
    Serial.print("Sending confirmation with ID: ");
    Serial.println(id);

    int code = http.POST(postData);
    if (code == 200) {
      Serial.println("Confirmation sent!");
    } else {
      Serial.printf("Failed to confirm: %d\n", code);
    }

    http.end();
  }
}

void fetchSchedule() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(scheduleUrl);
    int httpCode = http.GET();

    if (httpCode == 200) {
      String payload = http.getString();
      Serial.println("Server response:");
      Serial.println(payload);

      StaticJsonDocument<512> doc;
      DeserializationError error = deserializeJson(doc, payload);

      if (error) {
        Serial.print("JSON error: ");
        Serial.println(error.c_str());
        return;
      }

      if (doc.containsKey("scheduled_at")) {
        String timeStr = doc["scheduled_at"]; // Format: YYYY-MM-DD HH:MM:SS
        scheduledId = doc["id"];

        scheduledHour = timeStr.substring(11, 13).toInt();
        scheduledMinute = timeStr.substring(14, 16).toInt();

        Serial.print("Next medicine scheduled at ");
        Serial.print(scheduledHour);
        Serial.print(":");
        Serial.println(scheduledMinute);
      } else {
        Serial.println("No pending schedule found.");
        scheduledHour = -1;
        scheduledMinute = -1;
      }
    } else {
      Serial.printf("HTTP Error: %d\n", httpCode);
    }

    http.end();
  }
}
