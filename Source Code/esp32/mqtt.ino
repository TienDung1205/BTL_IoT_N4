#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include "DHT.h"

// ======= Cấu hình DHT22 =======
#define DHTPIN 15
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

// ======= Thiết bị =======
#define LIGHT_SENSOR_DO 25
#define LIGHT1 2
#define LIGHT2 4
#define FAN    23

// ======= WiFi =======
const char* ssid = "968";
const char* password = "88881989";

// ======= HiveMQ Cloud TLS =======
const char* mqtt_server = "530052fe99b94418a3414955fddef258.s1.eu.hivemq.cloud";
const int mqtt_port = 8883; // TLS Websocket
const char* mqtt_user = "smartHome";
const char* mqtt_pass = "Dung@123456";
const char* topic_pub = "smartHome/data";

// ======= MQTT Client =======
WiFiClientSecure secureClient;
PubSubClient mqttClient(secureClient);

// ======= Time =======
const char* ntpServer = "pool.ntp.org";
const long gmtOffset_sec = 7 * 3600;
const int daylightOffset_sec = 0;

void initTime() {
  configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
  time_t now = time(nullptr);
  while (now < 8 * 3600 * 2) {
    delay(500);
    Serial.print(".");
    now = time(nullptr);
  }
  Serial.println("\nTime synced");
}

// ======= Callback =======
void callback(char* topic, byte* payload, unsigned int length) {
  Serial.print("Received on topic: ");
  Serial.println(topic);

  String msg;
  for (int i = 0; i < length; i++) msg += (char)payload[i];
  Serial.println("Message: " + msg);

  // Điều khiển thiết bị
  if (msg.indexOf("light1") != -1) {
    digitalWrite(LIGHT1, msg.indexOf("ON") != -1 ? HIGH : LOW);
  }
  if (msg.indexOf("fan") != -1) {
    digitalWrite(FAN, msg.indexOf("ON") != -1 ? HIGH : LOW);
  }
}

// ======= Reconnect MQTT =======
void reconnect() {
  while (!mqttClient.connected()) {
    Serial.print("Connecting MQTT... ");
    if (mqttClient.connect("ESP32Client", mqtt_user, mqtt_pass)) {
      Serial.println("connected!");
      mqttClient.subscribe("smartHome/cmd");
    } else {
      Serial.print("Failed, rc=");
      Serial.print(mqttClient.state());
      Serial.println(" - retry in 2s");
      delay(2000);
    }
  }
}

void setup() {
  Serial.begin(115200);

  WiFi.begin(ssid, password);
  Serial.print("Connecting WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWi-Fi OK: " + WiFi.localIP().toString());

  initTime();

  secureClient.setInsecure();
  mqttClient.setServer(mqtt_server, mqtt_port);
  mqttClient.setCallback(callback);

  dht.begin();

  pinMode(LIGHT1, OUTPUT);
  pinMode(LIGHT2, OUTPUT);
  pinMode(FAN, OUTPUT);

  reconnect();
}

void loop() {
  if (!mqttClient.connected()) reconnect();
  mqttClient.loop();

  static unsigned long last = 0;
  if (millis() - last > 5000) {
    last = millis();

    float temp = dht.readTemperature();
    float hum = dht.readHumidity();

    if (isnan(temp) || isnan(hum)) {
      Serial.println("DHT fail!");
      return;
    }

    String payload = "{";
    payload += "\"light1\":\"" + String(digitalRead(LIGHT1) ? "ON" : "OFF") + "\",";
    payload += "\"light2\":\"" + String(digitalRead(LIGHT2) ? "ON" : "OFF") + "\",";
    payload += "\"fan\":\"" + String(digitalRead(FAN) ? "ON" : "OFF") + "\",";
    payload += "\"temp\":" + String(temp, 1) + ",";
    payload += "\"hum\":" + String(hum, 1);
    payload += "}";

    mqttClient.publish(topic_pub, payload.c_str());
    Serial.println("Published: " + payload);
  }
}
