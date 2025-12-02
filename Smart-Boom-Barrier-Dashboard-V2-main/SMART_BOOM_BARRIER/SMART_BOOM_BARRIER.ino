#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <Arduino.h>
#include <WiFi.h>
#include <LittleFS.h>
#include <AsyncTCP.h>
#include <ESPAsyncWebServer.h>
#include <DNSServer.h>
#include <vector>

DNSServer dnsServer;

// ==========================================
//              USER SETTINGS
// ==========================================
const char* AP_SSID = "ESP32-RFID-GATE";
const char* AP_PASS = "12345678";

// Multi-UID support: stored list of allowed UIDs
std::vector<String> allowedUIDs;

// ==========================================
//              NETWORK CONFIG
// ==========================================
IPAddress localIP(192,168,4,1);
IPAddress gateway(192,168,4,1);
IPAddress subnet(255,255,255,0);

AsyncWebServer server(80);
AsyncWebSocket ws("/ws");

// ==========================================
//              PIN DEFINITIONS
// ==========================================
#define SS_PIN    21  
#define RST_PIN   22
#define SCK_PIN   18
#define MISO_PIN  19
#define MOSI_PIN  23

MFRC522 rfid(SS_PIN, RST_PIN);

#define SERVO_PIN 13
Servo gateServo;

int SERVO_OPEN = 90;   
int SERVO_CLOSED = 0;  

#define TRIG_PIN 4
#define ECHO_PIN 15

String lastRFID = "";
bool gateOpen = false;
String prevRFID = "";
unsigned long lastScanMillis = 0;
const unsigned long SCAN_COOLDOWN = 3000; // ms to ignore repeated scans

// ==========================================
//              FILE SYSTEM FUNCTIONS
// ==========================================

// Persist the allowedUIDs vector to /uids.txt
void saveUIDs() {
  File f = LittleFS.open("/uids.txt", "w");
  if (!f) { Serial.println("Error: Could not open /uids.txt for writing"); return; }
  for (size_t i = 0; i < allowedUIDs.size(); ++i) {
    f.println(allowedUIDs[i]);
  }
  f.close();
  Serial.print("System: Saved UIDs (count="); Serial.print(allowedUIDs.size()); Serial.println(")");
}

// Load allowed UIDs from storage
void loadUIDs() {
  allowedUIDs.clear();
  // 1. Check for modern list file
  if (LittleFS.exists("/uids.txt")) {
    File f = LittleFS.open("/uids.txt", "r");
    if (f) {
      while (f.available()) {
        String line = f.readStringUntil('\n');
        line.trim();
        if (line.length() > 0) allowedUIDs.push_back(line);
      }
      f.close();
      Serial.print("System: Loaded UIDs (count="); Serial.print(allowedUIDs.size()); Serial.println(")");
    }
  } 
  // 2. Check for legacy single file (migration)
  else if (LittleFS.exists("/uid.txt")) {
    File file = LittleFS.open("/uid.txt", "r");
    if (file) {
      String u = file.readString(); u.trim();
      if (u.length() > 0) allowedUIDs.push_back(u);
      file.close();
      Serial.print("System: Loaded legacy UID: "); Serial.println(u);
      
      // Save to new format and delete old
      saveUIDs();
      LittleFS.remove("/uid.txt");
    }
  } else {
    Serial.println("System: No UIDs saved yet.");
  }
}

// Add a UID if not present
void addUID(String newUID) {
  newUID.trim();
  if (newUID.length() == 0) return;
  
  // Check duplicates
  for (auto &u : allowedUIDs) {
    if (u == newUID) { 
      Serial.println("System: UID already present: " + newUID); 
      return; 
    }
  }
  
  allowedUIDs.push_back(newUID);
  saveUIDs();
  Serial.println("System: Added UID: " + newUID);
}

// Remove stored UID if it matches the provided UID
void removeUID(String uidToRemove) {
  uidToRemove.trim();
  if (uidToRemove.length() == 0) return;
  
  bool removed = false;
  std::vector<String> newList;
  
  // Create new list excluding the one to remove
  for (auto &u : allowedUIDs) {
    if (u != uidToRemove) {
      newList.push_back(u);
    } else {
      removed = true;
    }
  }
  
  if (removed) {
    allowedUIDs = newList;
    // Overwrite the file with the new list
    saveUIDs();
    Serial.println("System: Removed UID: " + uidToRemove);
  }
}

// Check if UID exists in our list
bool uidExists(const String &uid) {
  for (auto &u : allowedUIDs) {
    if (u == uid) return true;
  }
  return false;
}

// ==========================================
//              UTILITY FUNCTIONS
// ==========================================

String uidToString(MFRC522::Uid *uid) {
  String s = "";
  for (byte i = 0; i < uid->size; i++) {
    if (uid->uidByte[i] < 0x10) s += "0";
    s += String(uid->uidByte[i], HEX);
  }
  s.toUpperCase();
  return s;
}

int getDistance() {
  digitalWrite(TRIG_PIN, LOW); delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH); delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);
  long duration = pulseIn(ECHO_PIN, HIGH, 30000); 
  if (duration == 0) return 100; 
  return duration * 0.15 / 2;
}

// ==========================================
//              GATE CONTROL
// ==========================================

void openGate() {
  if (!gateOpen) {
    gateServo.write(SERVO_OPEN);
    gateOpen = true;
    Serial.println("Gate: OPENING...");
    ws.textAll("{\"gate\":\"open\"}");
  }
}

void closeGate() {
  if (gateOpen) {
    gateServo.write(SERVO_CLOSED);
    gateOpen = false;
    Serial.println("Gate: CLOSING...");
    ws.textAll("{\"gate\":\"closed\"}");
  }
}

void activateGateSequence() {
  Serial.println(">>> Starting Gate Sequence");
  openGate();
  delay(3000);

  Serial.println("Checking for obstacles...");
  int distance = getDistance();
  // Safety: Don't close if something is closer than 20cm
  while (distance < 20 && distance > 0) { 
    ws.textAll("{\"status\":\"Obstacle Detected!\"}");
    delay(1000); 
    distance = getDistance();
  }
  Serial.println("Path clear.");
  closeGate();
}

// ==========================================
//           WEBSOCKET HANDLER
// ==========================================
void onWsEvent(AsyncWebSocket *server, AsyncWebSocketClient *client,
               AwsEventType type, void *arg, uint8_t *data, size_t len) {
  if (type == WS_EVT_DATA) {
    String msg = "";
    for (size_t i = 0; i < len; i++) msg += (char)data[i];
    msg.trim();
    
    if (msg == "open") openGate();
    else if (msg == "close") closeGate();
    
    // Commands from Dashboard
    else if (msg.startsWith("register:")) {
      String u = msg.substring(strlen("register:"));
      u.trim();
      if (u.length() > 0) {
        addUID(u);
        ws.textAll("{\"status\":\"UID Registered\", \"rfid\":\"" + u + "\"}");
      }
    } else if (msg.startsWith("remove:")) {
      String u = msg.substring(strlen("remove:"));
      u.trim();
      if (u.length() > 0) {
        removeUID(u);
        ws.textAll("{\"status\":\"UID Removed\", \"rfid\":\"" + u + "\"}");
      }
    }
  }
}

// ==========================================
//                 SETUP
// ==========================================
void setup() {
  Serial.begin(115200);
  delay(500);

  // 1. Filesystem
  if (!LittleFS.begin(true)) Serial.println("❌ LittleFS Failed");
  else {
    Serial.println("✅ LittleFS Loaded");
    loadUIDs(); 
  }

  // 2. WiFi AP
  WiFi.mode(WIFI_AP);
  WiFi.softAPConfig(localIP, gateway, subnet);
  WiFi.softAP(AP_SSID, AP_PASS);
  dnsServer.start(53, "*", localIP);
  Serial.println("WiFi AP Ready: " + WiFi.softAPIP().toString());

  // 3. Hardware
  SPI.begin(SCK_PIN, MISO_PIN, MOSI_PIN, SS_PIN);
  rfid.PCD_Init();
  gateServo.setPeriodHertz(50);
  gateServo.attach(SERVO_PIN, 500, 2400);
  gateServo.write(SERVO_CLOSED); 
  pinMode(TRIG_PIN, OUTPUT); pinMode(ECHO_PIN, INPUT);

  // 4. Web Server
  ws.onEvent(onWsEvent);
  server.addHandler(&ws);
  
  // HTTP REGISTER ENDPOINT (Optional backup to WebSocket)
  server.on("/register", HTTP_GET, [](AsyncWebServerRequest *request){
    if (request->hasParam("uid")) {
      String inputUID = request->getParam("uid")->value();
      addUID(inputUID);
      request->send(200, "text/plain", "UID Registered: " + inputUID);
      ws.textAll("{\"status\":\"Registered New UID: " + inputUID + "\"}");
    } else {
      request->send(400, "text/plain", "Error: No UID provided");
    }
  });

  server.serveStatic("/", LittleFS, "/").setDefaultFile("index.html");
  server.begin();
  
  Serial.println("✅ SYSTEM READY.");
}

// ==========================================
//                  LOOP
// ==========================================
void loop() {
  dnsServer.processNextRequest();
  ws.cleanupClients();

  if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    lastRFID = uidToString(&rfid.uid);
    lastRFID.trim(); 
    Serial.println("SCANNED TAG: " + lastRFID);

    // Debounce: ignore repeated scans of same tag within SCAN_COOLDOWN
    unsigned long now = millis();
    if (lastRFID == prevRFID && (now - lastScanMillis) < SCAN_COOLDOWN) {
      Serial.println("Ignoring repeated scan (debounce)");
      rfid.PICC_HaltA();
      rfid.PCD_StopCrypto1();
      continue;
    }

    // Send to Dashboard 
    ws.textAll("{\"rfid\":\"" + lastRFID + "\"}");

    // Check Access
    if (uidExists(lastRFID)) {
      Serial.println("STATUS: [ ACCESS GRANTED ]");
      ws.textAll("{\"status\":\"Access Granted\", \"rfid\":\"" + lastRFID + "\"}");
      activateGateSequence();
    } else {
      Serial.println("STATUS: [ ACCESS DENIED ]");
      
      // Auto-register ONLY if system is completely empty (First boot)
      if (allowedUIDs.size() == 0) {
        addUID(lastRFID);
        Serial.println("System: Registered MASTER UID (First run): " + lastRFID);
        ws.textAll("{\"status\":\"Master Card Registered\", \"rfid\":\"" + lastRFID + "\"}");
        activateGateSequence();
      } else {
        ws.textAll("{\"status\":\"Access Denied\", \"rfid\":\"" + lastRFID + "\"}");
        // Ensure gate stays closed
        if(gateOpen) closeGate();
      }
    }
    // record last scan
    prevRFID = lastRFID;
    lastScanMillis = millis();

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    // small delay to avoid immediate re-detection
    delay(150);
  }
}