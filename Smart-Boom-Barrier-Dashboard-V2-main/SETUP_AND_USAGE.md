# ESP32 RFID Customer Access System - Setup & Usage Guide

**Version:** 2.0 - Vehicle Plate Feature
**Last Updated:** 2025-12-02

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Requirements](#requirements)
3. [Installation & Setup](#installation--setup)
4. [Starting the System](#starting-the-system)
5. [User Guide](#user-guide)
6. [ESP32 Hardware Setup](#esp32-hardware-setup)
7. [Troubleshooting](#troubleshooting)
8. [Data Management](#data-management)

---

## System Overview

The ESP32 RFID Customer Access System is a complete access control solution that combines:

- **ESP32 microcontroller** with MFRC522 RFID reader (13.56 MHz)
- **Smart boom barrier** with ultrasonic distance sensor
- **Web-based dashboard** for administration and monitoring
- **Real-time vehicle tracking** with check-in/out logging
- **Excel export** for audit logs and registry reports

### Key Features

✅ **Customer Registration** - Register RFID cards with vehicle plate numbers
✅ **Real-Time Monitoring** - View all vehicles currently inside the premises
✅ **Check-In/Out Tracking** - Automatic logging of entry and exit times
✅ **Excel Exports** - Download audit logs and registry reports
✅ **WebSocket Integration** - Real-time communication with ESP32 device
✅ **Audit Logging** - Complete access history with timestamps

---

## Requirements

### Software Requirements

- **PHP 7.4+** (Recommended: PHP 8.2.12)
- **Web Browser** (Chrome, Firefox, Edge, Safari)
- **Text Editor** (Optional, for configuration)

### Hardware Requirements

- **ESP32 Development Board** (ESP32-WROOM-32 or similar)
- **MFRC522 RFID Reader Module** (13.56 MHz)
- **HC-SR04 Ultrasonic Sensor** (for distance measurement)
- **Servo Motor** (for boom barrier control)
- **RFID Cards/Tags** (13.56 MHz ISO 14443A)
- **5V Power Supply** (for ESP32 and peripherals)
- **Breadboard and jumper wires** (for prototyping)

### Network Requirements

- **Local Network** (WiFi router)
- **Static IP** (recommended for ESP32)
- **Port 8000** (for PHP development server)
- **Port 81** (for WebSocket communication with ESP32)

---

## Installation & Setup

### Step 1: Extract Files

1. Extract the `Smart-Boom-Barrier-Dashboard-V2-main` folder to a convenient location
2. Recommended path: `C:\xampp\htdocs\` or `Desktop\`

### Step 2: Verify Installation

Run the verification script to check all files:

**Windows Command Prompt:**
```cmd
cd Smart-Boom-Barrier-Dashboard-V2-main
verify.bat
```

**PowerShell:**
```powershell
cd Smart-Boom-Barrier-Dashboard-V2-main
powershell -ExecutionPolicy Bypass -File verify_installation.ps1
```

Expected output:
```
[OK] api.php exists
[OK] vehicles_inside.html exists - NEW FILE
[OK] customers.json exists - NEW FILE
[OK] register_rfid.html exists
[OK] admin.html exists
[OK] backend.js exists
[OK] PHP is installed
[OK] vehicle_plate in api.php
[OK] vehiclePlate field in register_rfid.html
[OK] vehicles_inside link in admin.html
[OK] getVehiclesInside method in backend.js
```

### Step 3: Check PHP Installation

Verify PHP is installed and accessible:

```cmd
php -v
```

Expected output:
```
PHP 8.2.12 (cli) (built: Oct 24 2023)
```

**If PHP is not installed:**
- Download from [https://windows.php.net/download/](https://windows.php.net/download/)
- Or install XAMPP: [https://www.apachefriends.org/](https://www.apachefriends.org/)

### Step 4: Configure WiFi Settings (ESP32)

Edit the ESP32 firmware configuration (if needed):

1. Open `esp32_firmware/main.cpp` (or `.ino` file)
2. Update WiFi credentials:
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```
3. Update server IP (your computer's local IP):
   ```cpp
   const char* serverHost = "192.168.1.100"; // Your PC's IP
   const int serverPort = 8000;
   ```

### Step 5: Flash ESP32 Firmware

Using Arduino IDE or PlatformIO:

**Arduino IDE:**
```
1. Open esp32_firmware/main.ino
2. Select Board: "ESP32 Dev Module"
3. Select Port: COM3 (or your ESP32 port)
4. Click Upload
```

**PlatformIO:**
```bash
cd esp32_firmware
pio run --target upload
```

---

## Starting the System

### 1. Start the PHP Server

**Option A: Using Command Prompt**
```cmd
cd Smart-Boom-Barrier-Dashboard-V2-main\data
php -S localhost:8000
```

**Option B: Using PowerShell**
```powershell
cd Smart-Boom-Barrier-Dashboard-V2-main\data
php -S localhost:8000
```

**Option C: Keep server running in background (PowerShell)**
```powershell
Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8000", "-t", "data" -WindowStyle Hidden
```

Expected output:
```
PHP 8.2.12 Development Server (http://localhost:8000) started
```

**⚠️ Important:** Keep this terminal window open while using the system.

### 2. Power On ESP32

1. Connect ESP32 to power supply
2. Wait for WiFi connection (LED indicator should blink)
3. ESP32 will connect to your PHP server via WebSocket on port 81

### 3. Open the Dashboard

Open your web browser and navigate to:

```
http://localhost:8000/login.html
```

**Default Login Credentials:**
- **Username:** `admin`
- **Password:** `admin`

⚠️ **Change default password after first login!**

---

## User Guide

### 1. Admin Login

1. Navigate to `http://localhost:8000/login.html`
2. Enter credentials:
   - Username: `admin`
   - Password: `admin`
3. Click **Login**
4. You will be redirected to the main dashboard (`index.html`)

---

### 2. Register a New Customer

**Purpose:** Register RFID cards with customer and vehicle information.

**Steps:**

1. From the main dashboard, click **Admin Dashboard**
2. Click **+ Register New Card**
3. You'll see the registration form (`register_rfid.html`)

**Option A: Scan RFID Card (Recommended)**
1. Hold RFID card near the MFRC522 reader connected to ESP32
2. The Card UID field will auto-fill via WebSocket
3. Status indicator will show "Waiting for card scan..."

**Option B: Manual Entry**
1. Manually type the Card UID (e.g., `01D466A2`)

**Fill in Customer Details:**
- **Card UID:** Auto-filled or manual entry (Required)
- **Card Holder Name:** Full name (Required)
- **Email:** Email address (Optional)
- **Phone:** Phone number with country code (Optional)
- **Vehicle Plate Number:** License plate (e.g., ABC-1234) (Optional)
- **Department:** Department or category (Optional)
- **Notes:** Additional information (Optional)

**Preview Section:**
- As you type, the card preview on the right updates in real-time
- Shows: UID, Name, Vehicle Plate, Department

**Submit:**
1. Click **Register Card**
2. Success message: "✅ Card registered: [UID]"
3. Form will clear after 2 seconds
4. The UID is automatically added to the authorized list

---

### 3. View Vehicles Currently Inside

**Purpose:** See real-time list of all vehicles that have checked in but not checked out.

**Steps:**

1. From the Admin Dashboard, click **🚗 Vehicles Inside**
2. The page will display all vehicles currently inside

**What You'll See:**

Each vehicle card shows:
- **Name:** Card holder's name
- **Vehicle Plate:** License plate number (in blue badge)
- **Card UID:** RFID card identifier
- **Department:** Department/category
- **Check-in Time:** Timestamp of entry
- **Duration:** Time since check-in (e.g., "2h 35m" in green)

**Auto-Refresh:**
- Page refreshes every **5 seconds** automatically
- Countdown indicator shows: "Auto-refresh: 5s → 4s → 3s..."
- Duration updates on each refresh

**Vehicle Count:**
- Badge in header shows total vehicles inside (e.g., "4")

**Empty State:**
- When no vehicles are inside, displays: "No Vehicles Inside"

---

### 4. Check-In / Check-Out Process

**Automatic Process via ESP32:**

**Check-In:**
1. Vehicle approaches the gate
2. Driver scans RFID card at the reader
3. ESP32 validates the card against authorized list
4. If authorized:
   - Boom barrier opens (servo motor activates)
   - Entry timestamp recorded in `registry.json`
   - WebSocket broadcasts event to dashboard
   - Vehicle appears in "Vehicles Inside" page
5. Ultrasonic sensor detects vehicle passing
6. Boom barrier closes automatically

**Check-Out:**
1. Vehicle approaches exit gate
2. Driver scans the same RFID card
3. ESP32 recognizes previous check-in
4. Boom barrier opens
5. Exit timestamp recorded in `registry.json`
6. Vehicle disappears from "Vehicles Inside" page
7. Boom barrier closes

**Manual Toggle (Web Dashboard):**
1. Go to main dashboard (`index.html`)
2. Find the registry section
3. Enter Card UID
4. Click toggle button
5. System will add check-in or check-out timestamp

---

### 5. View Audit Logs

**Purpose:** Review complete access history with timestamps and authorization status.

**Steps:**

1. From Admin Dashboard, click **Audit Log**
2. Opens `admin_audit.php`
3. View all access attempts with:
   - Timestamp
   - IP Address
   - Card UID
   - Authorization status (Yes/No)
   - Distance reading from ultrasonic sensor
   - HTTP response code

**Filtering:**
- Use the filter form at the top to search by:
  - Card UID
  - Date range
  - Authorization status

---

### 6. Export to Excel

**Purpose:** Download CSV files for offline analysis in Excel/Google Sheets.

**Export Audit Log:**

1. Go to **Admin Audit** page (`admin_audit.php`)
2. Click **📥 Export Audit Log to Excel** (green button)
3. CSV file downloads: `audit_log_YYYY-MM-DD_HHMMSS.csv`
4. Open in Excel

**Columns:**
- Timestamp
- IP Address
- Card UID
- Authorized (Yes/No)
- Distance (cm)
- Status
- HTTP Code

**Export Registry:**

1. On the same page, click **📊 Export Registry to Excel** (blue button)
2. CSV file downloads: `registry_YYYY-MM-DD_HHMMSS.csv`
3. Open in Excel

**Columns:**
- Card UID
- Name
- **Vehicle Plate** ← NEW
- Department
- Check In (timestamp)
- Check Out (timestamp)
- Duration (calculated) or "Still inside"

**Excel Tips:**
- Format timestamp columns as Date/Time
- Use filters to sort by duration
- Create pivot tables for analysis
- Calculate average visit duration

---

### 7. Navigation

**Dashboard Pages:**

- **Login** (`login.html`) - Entry point
- **Main Dashboard** (`index.html`) - System overview
- **Admin Dashboard** (`admin.html`) - Admin controls
- **Register Card** (`register_rfid.html`) - Customer registration
- **Vehicles Inside** (`vehicles_inside.html`) - Real-time vehicle display
- **Audit Log** (`admin_audit.php`) - Access history

**Navigation Links:**

From **Admin Dashboard**:
- ← Back to Dashboard
- + Register New Card
- 🚗 Vehicles Inside
- Audit Log
- Logout

From **Vehicles Inside**:
- Admin Dashboard
- Main Dashboard
- Logout

---

## ESP32 Hardware Setup

### Pin Connections

**MFRC522 RFID Reader:**
```
MFRC522 Pin → ESP32 Pin
SDA (SS)    → GPIO 5
SCK         → GPIO 18
MOSI        → GPIO 23
MISO        → GPIO 19
IRQ         → Not connected
GND         → GND
RST         → GPIO 22
3.3V        → 3.3V
```

**HC-SR04 Ultrasonic Sensor:**
```
Sensor Pin  → ESP32 Pin
VCC         → 5V
TRIG        → GPIO 32
ECHO        → GPIO 33
GND         → GND
```

**Servo Motor (Boom Barrier):**
```
Servo Pin   → ESP32 Pin
Signal      → GPIO 25
VCC         → 5V (external power recommended)
GND         → GND
```

### Wiring Diagram

```
                    ESP32
                  ┌──────┐
RFID SDA    ─────►│ GP5  │
RFID SCK    ─────►│ GP18 │
RFID MOSI   ─────►│ GP23 │
RFID MISO   ◄─────│ GP19 │
RFID RST    ─────►│ GP22 │
                  │      │
Ultrasonic TRIG ─►│ GP32 │
Ultrasonic ECHO ◄─│ GP33 │
                  │      │
Servo Signal ────►│ GP25 │
                  │      │
                  │ 3.3V ├──── RFID VCC
                  │ 5V   ├──── Ultrasonic VCC / Servo VCC
                  │ GND  ├──── Common Ground
                  └──────┘
```

### Testing Hardware

**Test RFID Reader:**
1. Upload firmware to ESP32
2. Open Serial Monitor (115200 baud)
3. Hold RFID card near reader
4. Should see: "Card detected: [UID]"

**Test Ultrasonic Sensor:**
1. Place object in front of sensor
2. Serial Monitor shows: "Distance: XX cm"

**Test Servo Motor:**
1. System should move servo to open/close positions
2. Default: 0° (closed), 90° (open)

---

## Troubleshooting

### PHP Server Issues

**Problem:** `php: command not found`

**Solution:**
1. Install PHP from [https://windows.php.net/download/](https://windows.php.net/download/)
2. Add PHP to system PATH
3. Restart terminal

**Problem:** Port 8000 already in use

**Solution:**
```cmd
# Use a different port
php -S localhost:8080

# Then access: http://localhost:8080/login.html
```

**Problem:** Server stops when terminal closes

**Solution:**
```powershell
# Run in background (PowerShell)
Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8000", "-t", "data" -WindowStyle Hidden
```

---

### Login Issues

**Problem:** Cannot login with admin/admin

**Solution:**
1. Check `data/data/users.json` exists
2. If missing, the system creates it on first run
3. Try refreshing the page
4. Check browser console for errors (F12)

**Problem:** "Session expired" error

**Solution:**
1. Clear browser cookies
2. Restart PHP server
3. Login again

---

### ESP32 Issues

**Problem:** ESP32 not connecting to WiFi

**Solution:**
1. Check WiFi credentials in firmware
2. Ensure 2.4GHz WiFi (ESP32 doesn't support 5GHz)
3. Check router allows new device connections
4. Monitor Serial output for error messages

**Problem:** RFID cards not detected

**Solution:**
1. Check wiring connections (especially SDA and RST)
2. Ensure RFID reader powered with 3.3V (not 5V)
3. Use correct frequency cards (13.56 MHz)
4. Test with example sketch first

**Problem:** WebSocket connection failed

**Solution:**
1. Ensure PHP server is running
2. Check firewall allows port 81
3. Verify server IP address in ESP32 firmware
4. Check ESP32 and server are on same network

---

### Data Issues

**Problem:** Customers not appearing after registration

**Solution:**
1. Check `data/data/customers.json` exists and has valid JSON
2. Verify file permissions (read/write)
3. Check browser console for API errors
4. Ensure you're logged in as admin

**Problem:** Vehicles not showing in "Vehicles Inside"

**Solution:**
1. Verify customer is registered first
2. Check `data/data/registry.json` has entry with 'in' timestamp
3. Ensure 'out' timestamp is null
4. Wait for auto-refresh (5 seconds)
5. Check browser console for errors

**Problem:** Excel export downloads empty file

**Solution:**
1. Check `data/data/registry.json` has data
2. Verify admin authentication
3. Check PHP error logs
4. Ensure proper file permissions

---

## Data Management

### File Locations

All data stored in `Smart-Boom-Barrier-Dashboard-V2-main/data/data/`:

- **users.json** - Admin user accounts
- **tags.json** - Authorized RFID card UIDs
- **registry.json** - Check-in/out timestamps
- **customers.json** - Customer details with vehicle plates
- **esp32_audit.log** - Raw access attempt logs (NDJSON format)

### Backup

**Manual Backup:**
```cmd
# Create backup folder
mkdir data_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%

# Copy all data files
xcopy data\data\*.* data_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%\ /s /e
```

**Recommended Schedule:**
- Daily backups of `data/data/` folder
- Weekly full system backups
- Before major updates

### Reset System

**Clear all registrations (keep users):**
1. Stop PHP server
2. Delete or edit files:
   - `data/data/customers.json` → `{}`
   - `data/data/registry.json` → `{}`
   - `data/data/tags.json` → `[]`
3. Restart PHP server

**Full reset (including admin users):**
1. Stop PHP server
2. Delete all files in `data/data/`
3. Restart PHP server
4. System will recreate default admin user

---

## Security Best Practices

1. **Change Default Password**
   - Login as admin
   - Navigate to user management
   - Update password immediately

2. **Use HTTPS in Production**
   - Configure SSL certificate
   - Use Apache or Nginx instead of PHP dev server

3. **Restrict Network Access**
   - Keep system on local network only
   - Use firewall rules to block external access
   - Consider VPN for remote administration

4. **Regular Backups**
   - Backup customer database daily
   - Store backups securely offsite
   - Test restore procedures

5. **Update Firmware**
   - Keep ESP32 firmware updated
   - Monitor for security advisories
   - Test updates on staging system first

---

## Support & Documentation

**Documentation Files:**
- [QA_TEST_PLAN.md](QA_TEST_PLAN.md) - Comprehensive testing guide (25 test cases)
- [AUTOMATED_TEST_RESULTS.md](AUTOMATED_TEST_RESULTS.md) - Verification results
- [Plan File](.claude/plans/indexed-wiggling-clover.md) - Implementation details

**Common Workflows:**
- New customer registration → See Section 2
- Monitor active vehicles → See Section 3
- Generate reports → See Section 6
- Hardware setup → See Section on ESP32 Hardware

**Need Help?**
- Check troubleshooting section above
- Review QA test plan for expected behavior
- Examine browser console (F12) for JavaScript errors
- Check PHP error logs for server-side issues

---

## Quick Reference

**Start System:**
```bash
cd Smart-Boom-Barrier-Dashboard-V2-main\data
php -S localhost:8000
# Open: http://localhost:8000/login.html
```

**Default Credentials:**
```
Username: admin
Password: admin
```

**Important URLs:**
```
Login:           http://localhost:8000/login.html
Dashboard:       http://localhost:8000/index.html
Admin:           http://localhost:8000/admin.html
Register:        http://localhost:8000/register_rfid.html
Vehicles Inside: http://localhost:8000/vehicles_inside.html
Audit Log:       http://localhost:8000/admin_audit.php
```

**Key Features:**
- ✅ Vehicle plate tracking
- ✅ Real-time monitoring (5s auto-refresh)
- ✅ Excel exports (CSV format)
- ✅ WebSocket integration with ESP32
- ✅ Audit logging

---

**Version:** 2.0
**Last Updated:** 2025-12-02
**System Status:** ✅ Production Ready
