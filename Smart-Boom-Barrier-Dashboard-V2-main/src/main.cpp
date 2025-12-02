#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <WiFi.h>
#include <WebServer.h>
#include <WebSocketsServer.h>
#include <LittleFS.h>
#include <FS.h>

#define RST_PIN   22
#define SS_PIN    21
#define TRIG_PIN   4
#define ECHO_PIN  15
#define SERVO_PIN 13

MFRC522 mfrc522(SS_PIN, RST_PIN);

// Authorized UIDs (hex, uppercase, no separators)
const char* authorizedUids[] = { "DEADBEEF" };
const uint8_t AUTH_UID_COUNT = sizeof(authorizedUids) / sizeof(authorizedUids[0]);

const char* AP_SSID = "ESP32-RFID-AP";
const char* AP_PASS = "12345678";

WebServer server(80);
WebSocketsServer webSocket = WebSocketsServer(81);

Servo gateServo;
const int SERVO_OPEN = 90;
const int SERVO_CLOSE = 0;

String lastUID = "";
bool lastAuth = false;
int lastDistance = 0;

String uidToHexString(uint8_t *uid, uint8_t uidSize) {
  String s = "";
  char buf[3];
  for (uint8_t i = 0; i < uidSize; i++) {
    sprintf(buf, "%02X", uid[i]);
    s += buf;
  }
  return s;
}

bool isAuthorized(const char* uid) {
  for (uint8_t i = 0; i < AUTH_UID_COUNT; i++) {
    if (strcmp(uid, authorizedUids[i]) == 0) return true;
  }
  return false;
}

unsigned long readUltrasonicCm() {
  digitalWrite(TRIG_PIN, LOW); delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH); delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 30000);
  if (duration == 0) return 0xFFFFFFFF;
  return duration / 58;
}

void openGate() {
  gateServo.write(SERVO_OPEN);
  delay(3000);
  gateServo.write(SERVO_CLOSE);
}

void broadcastStatus() {
  String msg = "{\"uid\":\"" + lastUID + "\",\"auth\":" + (lastAuth ? "true" : "false") + ",\"distance\":" + String(lastDistance) + "}";
  webSocket.broadcastTXT(msg);
}

void webSocketEvent(uint8_t num, WStype_t type, uint8_t * payload, size_t length) {
  switch (type) {
    case WStype_DISCONNECTED:
      Serial.printf("WS[%u] Disconnected\n", num);
      break;
    case WStype_CONNECTED: {
      IPAddress ip = webSocket.remoteIP(num);
      Serial.printf("WS[%u] Connected from %d.%d.%d.%d\n", num, ip[0], ip[1], ip[2], ip[3]);
      broadcastStatus();
      break;
    }
    case WStype_TEXT: {
      String msg = String((char*)payload, length);
      Serial.printf("WS[%u] Msg: %s\n", num, msg.c_str());
      if (msg == "open") openGate();
      if (msg == "status") broadcastStatus();
      break;
    }
    default:
      break;
  }
}

void handleRoot() {
  if (LittleFS.exists("/index.html")) {
    File f = LittleFS.open("/index.html", "r");
    server.streamFile(f, "text/html");
    f.close();
  } else {
    server.send(200, "text/plain", "Upload index.html to LittleFS");
  }
}

void setup() {
  Serial.begin(115200);
  delay(200);

  if (!LittleFS.begin(true)) Serial.println("LittleFS mount failed");

  WiFi.mode(WIFI_AP);
  WiFi.softAP(AP_SSID, AP_PASS);
  Serial.print("AP IP: "); Serial.println(WiFi.softAPIP());

  webSocket.begin();
  webSocket.onEvent(webSocketEvent);

  server.on("/", HTTP_GET, handleRoot);
  server.begin();

  SPI.begin(18, 19, 23, SS_PIN);
  mfrc522.PCD_Init();

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);

  gateServo.attach(SERVO_PIN);
  gateServo.write(SERVO_CLOSE);

  Serial.println("System Ready!");
}

void loop() {
  server.handleClient();
  webSocket.loop();

  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    lastUID = uidToHexString(mfrc522.uid.uidByte, mfrc522.uid.size);
    Serial.println("RFID: " + lastUID);

    lastAuth = isAuthorized(lastUID.c_str());

    if (lastAuth) {
      unsigned long d = readUltrasonicCm();
      if (d == 0xFFFFFFFF) lastDistance = 0; else lastDistance = (int)d;
      if (lastDistance > 50) openGate();
    }

    broadcastStatus();

    mfrc522.PICC_HaltA();
  }
}
