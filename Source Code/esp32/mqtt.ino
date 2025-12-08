#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include "DHT.h"
#include <HTTPClient.h>

// ======= Cấu hình DHT22 =======
#define DHTPIN 15
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

// ======= Thiết bị =======
#define LIGHT_SENSOR_DO 25
#define LIGHT1 2
#define LIGHT2 4
#define FAN 23

// ======= WiFi =======
const char *ssid = "Nha 10B ngo 204";
const char *password = "11223344";

// ======= HiveMQ Cloud TLS =======
const char *mqtt_server = "530052fe99b94418a3414955fddef258.s1.eu.hivemq.cloud";
const int mqtt_port = 8883; // TLS Websocket
const char *mqtt_user = "smartHome";
const char *mqtt_pass = "Hieu@123456";
const char *topic_pub = "smartHome/data";

// ======= Adafruit IO Configuration =======
const char *adafruit_username = "dugnam18";
const char *adafruit_key = "abc";
const char *adafruit_feed = "light";
const char *adafruit_url = "https://io.adafruit.com/api/v2/dugnam18/feeds/light/data";
WiFiClientSecure adafruitClient;

// ======= MQTT Client =======
WiFiClientSecure secureClient;
PubSubClient mqttClient(secureClient);

// ======= Time =======
const char *ntpServer = "pool.ntp.org";
const long gmtOffset_sec = 7 * 3600;
const int daylightOffset_sec = 0;

void initTime()
{
  configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
  time_t now = time(nullptr);
  while (now < 8 * 3600 * 2)
  {
    delay(500);
    Serial.print(".");
    now = time(nullptr);
  }
  Serial.println("\nTime synced");
}

// ======= Callback =======
void callback(char *topic, byte *payload, unsigned int length)
{
  Serial.print("Received on topic: ");
  Serial.println(topic);

  String msg;
  for (int i = 0; i < length; i++)
    msg += (char)payload[i];
  Serial.println("Message: " + msg);

  // Điều khiển thiết bị
  if (msg.indexOf("light1") != -1)
  {
    digitalWrite(LIGHT1, msg.indexOf("ON") != -1 ? HIGH : LOW);
  }
  if (msg.indexOf("light2") != -1)
  {
    digitalWrite(LIGHT2, msg.indexOf("ON") != -1 ? HIGH : LOW);
  }
  if (msg.indexOf("fan") != -1)
  {
    digitalWrite(FAN, msg.indexOf("ON") != -1 ? HIGH : LOW);
  }
}

// ======= Adafruit API Call =======
void sendToAdafruit(String lightValue)
{
  if (WiFi.status() != WL_CONNECTED)
  {
    Serial.println("WiFi not connected");
    return;
  }

  HTTPClient http;
  adafruitClient.setInsecure();

  String url = String(adafruit_url);
  url += "?X-AIO-Key=" + String(adafruit_key);

  http.begin(adafruitClient, url);
  http.addHeader("Content-Type", "application/json");

  // Create JSON payload
  String payload = "{\"value\":\"" + lightValue + "\"}";

  Serial.print("Sending to Adafruit: ");
  Serial.println(payload);

  int httpCode = http.POST(payload);

  if (httpCode > 0)
  {
    Serial.print("HTTP Response code: ");
    Serial.println(httpCode);
    if (httpCode == HTTP_CODE_OK || httpCode == HTTP_CODE_CREATED)
    {
      Serial.println("Successfully sent to Adafruit IO");
    }
  }
  else
  {
    Serial.print("HTTP Error: ");
    Serial.println(http.errorToString(httpCode));
  }

  http.end();
}

// ======= Get Light Status =======
String getLightStatus()
{
  String light1_status = digitalRead(LIGHT1) ? "ON" : "OFF";
  String light2_status = digitalRead(LIGHT2) ? "ON" : "OFF";
  return light1_status + "|" + light2_status;
}

// ======= Get Light Control from Adafruit =======
void getFromAdafruit()
{
  if (WiFi.status() != WL_CONNECTED)
  {
    Serial.println("WiFi not connected");
    return;
  }

  HTTPClient http;
  adafruitClient.setInsecure();

  String url = String(adafruit_url);
  url += "?X-AIO-Key=" + String(adafruit_key);

  http.begin(adafruitClient, url);

  int httpCode = http.GET();

  if (httpCode > 0)
  {
    Serial.print("HTTP GET Response code: ");
    Serial.println(httpCode);

    if (httpCode == HTTP_CODE_OK)
    {
      String response = http.getString();
      Serial.print("Response from Adafruit: ");
      Serial.println(response);

      // Parse JSON response to get the latest value
      // Response format: [{"value":"ON|ON","created_at":"..."}]
      int valueStart = response.indexOf("\"value\":\"");
      if (valueStart != -1)
      {
        valueStart += 9; // length of "\"value\":\""
        int valueEnd = response.indexOf("\"", valueStart);
        String lightValue = response.substring(valueStart, valueEnd);

        Serial.print("Light value from Adafruit: ");
        Serial.println(lightValue);

        // Parse light1 and light2 status (format: "ON|ON" or "OFF|OFF", etc)
        int pipeIndex = lightValue.indexOf("|");
        if (pipeIndex != -1)
        {
          String light1_cmd = lightValue.substring(0, pipeIndex);
          String light2_cmd = lightValue.substring(pipeIndex + 1);

          Serial.print("Light1: ");
          Serial.print(light1_cmd);
          Serial.print(" | Light2: ");
          Serial.println(light2_cmd);

          // Control both lights
          digitalWrite(LIGHT1, light1_cmd == "ON" ? HIGH : LOW);
          digitalWrite(LIGHT2, light2_cmd == "ON" ? HIGH : LOW);

          Serial.println("Lights updated from Adafruit");
        }
      }
    }
  }
  else
  {
    Serial.print("HTTP GET Error: ");
    Serial.println(http.errorToString(httpCode));
  }

  http.end();
}

// ======= Reconnect MQTT =======
void reconnect()
{
  while (!mqttClient.connected())
  {
    Serial.print("Connecting MQTT... ");
    if (mqttClient.connect("ESP32Client", mqtt_user, mqtt_pass))
    {
      Serial.println("connected!");
      mqttClient.subscribe("smartHome/cmd");
    }
    else
    {
      Serial.print("Failed, rc=");
      Serial.print(mqttClient.state());
      Serial.println(" - retry in 2s");
      delay(2000);
    }
  }
}

void setup()
{
  Serial.begin(115200);

  WiFi.begin(ssid, password);
  Serial.print("Connecting WiFi");
  while (WiFi.status() != WL_CONNECTED)
  {
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

void loop()
{
  if (!mqttClient.connected())
    reconnect();
  mqttClient.loop();

  static unsigned long last = 0;
  static unsigned long adafruit_last = 0;

  if (millis() - last > 5000)
  {
    last = millis();

    float temp = dht.readTemperature();
    float hum = dht.readHumidity();

    if (isnan(temp) || isnan(hum))
    {
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

  // Send light status to Adafruit every 2 seconds
  if (millis() - adafruit_last > 2000)
  {
    adafruit_last = millis();
    String lightStatus = getLightStatus();
    sendToAdafruit(lightStatus);
  }

  // Get light control from Adafruit every 3 seconds
  static unsigned long adafruit_get_last = 0;
  if (millis() - adafruit_get_last > 3000)
  {
    adafruit_get_last = millis();
    getFromAdafruit();
  }
}
