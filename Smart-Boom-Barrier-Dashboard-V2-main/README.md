# ESP32 RFID Customer Access System

> **Smart Boom Barrier Dashboard V2** - Enhanced with Vehicle Plate Tracking

A complete IoT access control system combining ESP32 hardware with a web-based management dashboard for monitoring vehicle access with RFID authentication.

![Version](https://img.shields.io/badge/version-2.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2.12-777BB4.svg)
![ESP32](https://img.shields.io/badge/ESP32-Arduino-00979D.svg)
![Status](https://img.shields.io/badge/status-production%20ready-success.svg)

---

## 🚀 What's New in V2.0

✅ **Vehicle Plate Number Tracking** - Register and track vehicle license plates
✅ **Real-Time Vehicle Display** - See all vehicles currently inside with auto-refresh
✅ **Enhanced Excel Exports** - Export audit logs and registry with vehicle data
✅ **Improved UI/UX** - Responsive design with real-time preview
✅ **JSON Storage** - All data stored in JSON format for easy management

---

## 📋 Features

### Hardware Integration
- 🔌 **ESP32 Microcontroller** with WiFi connectivity
- 📡 **MFRC522 RFID Reader** (13.56 MHz)
- 📏 **HC-SR04 Ultrasonic Sensor** for vehicle detection
- 🚧 **Servo Motor Control** for boom barrier
- 🔄 **WebSocket Communication** for real-time updates

### Web Dashboard
- 👤 **Admin Authentication** with secure login
- 📝 **Customer Registration** with vehicle plate numbers
- 🚗 **Real-Time Vehicle Monitoring** with auto-refresh every 5 seconds
- ⏱️ **Check-In/Out Tracking** with duration calculation
- 📊 **Excel Export** for audit logs and registry reports
- 🔍 **Audit Logging** with complete access history
- 📱 **Responsive Design** for mobile and desktop

### Data Management
- 💾 **JSON Storage** for all customer and registry data
- 🔒 **bcrypt Password Hashing** for secure authentication
- 🔐 **Session-Based Auth** with timeout protection
- 📄 **NDJSON Audit Logs** for comprehensive access tracking
- 🔄 **File Locking** to prevent concurrent access issues

---

## 📦 Quick Start

### Prerequisites

- **PHP 7.4+** (PHP 8.2.12 recommended)
- **Web Browser** (Chrome, Firefox, Edge, Safari)
- **ESP32 Development Board** with accessories

### Installation

1. **Extract files** to your desired location:
   ```bash
   cd Desktop
   # Extract Smart-Boom-Barrier-Dashboard-V2-main.zip
   ```

2. **Verify installation**:
   ```cmd
   cd Smart-Boom-Barrier-Dashboard-V2-main
   verify.bat
   ```

3. **Start the PHP server**:
   ```cmd
   cd data
   php -S localhost:8000
   ```

4. **Open your browser**:
   ```
   http://localhost:8000/login.html
   ```

5. **Login with default credentials**:
   - Username: `admin`
   - Password: `admin`

**⚠️ Change the default password after first login!**

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| **[SETUP_AND_USAGE.md](SETUP_AND_USAGE.md)** | Complete setup guide and user manual |
| **[QA_TEST_PLAN.md](QA_TEST_PLAN.md)** | Comprehensive testing guide (25 test cases) |
| **[AUTOMATED_TEST_RESULTS.md](AUTOMATED_TEST_RESULTS.md)** | Automated verification results |

---

## 🗂️ Project Structure

```
Smart-Boom-Barrier-Dashboard-V2-main/
│
├── data/                          # Web application files
│   ├── api.php                    # Backend API (5 endpoints + helpers)
│   ├── login.html                 # Login page
│   ├── index.html                 # Main dashboard
│   ├── admin.html                 # Admin dashboard
│   ├── register_rfid.html         # Customer registration form
│   ├── vehicles_inside.html       # Real-time vehicle display (NEW)
│   ├── admin_audit.php            # Audit log viewer with Excel exports
│   ├── backend.js                 # Frontend API wrapper
│   ├── styles.css                 # Global styles
│   │
│   └── data/                      # JSON data storage
│       ├── users.json             # Admin users (bcrypt hashed)
│       ├── tags.json              # Authorized RFID UIDs
│       ├── registry.json          # Check-in/out records
│       ├── customers.json         # Customer & vehicle data (NEW)
│       └── esp32_audit.log        # Access attempt logs (NDJSON)
│
├── esp32_firmware/                # ESP32 source code
│   ├── main.cpp / main.ino        # Main firmware
│   ├── platformio.ini             # PlatformIO config
│   └── lib/                       # Libraries (MFRC522, Servo, etc.)
│
├── SETUP_AND_USAGE.md             # Setup guide and user manual
├── QA_TEST_PLAN.md                # Testing documentation
├── AUTOMATED_TEST_RESULTS.md      # Verification results
├── verify.bat                     # Quick verification script
└── README.md                      # This file
```

---

## 🔧 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         WEB BROWSER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Login      │  │  Dashboard   │  │  Vehicles    │          │
│  │   Page       │  │   (Admin)    │  │   Inside     │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                  │                  │                  │
│         └──────────────────┼──────────────────┘                  │
└────────────────────────────┼─────────────────────────────────────┘
                             │ HTTP/POST
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      PHP SERVER (Port 8000)                      │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  api.php - Backend API                                    │  │
│  │  • register_rfid        • get_customers                   │  │
│  │  • get_vehicles_inside  • export_registry_excel          │  │
│  │  • export_audit_excel                                     │  │
│  └───────────────────────┬──────────────────────────────────┘  │
│                          │                                      │
│  ┌───────────────────────▼──────────────────────────────────┐  │
│  │  JSON Storage (data/data/)                               │  │
│  │  • customers.json  • registry.json  • tags.json         │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                             ▲
                             │ WebSocket (Port 81)
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                       ESP32 HARDWARE                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │  MFRC522     │  │  HC-SR04     │  │    Servo     │          │
│  │  RFID Reader │  │  Ultrasonic  │  │    Motor     │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                  │                  │                  │
│         └──────────────────┼──────────────────┘                  │
│                            ▼                                     │
│                    ESP32 Firmware                                │
│              (WiFi + WebSocket Client)                           │
└─────────────────────────────────────────────────────────────────┘
                             │
                             ▼
                    🚧 BOOM BARRIER
```

---

## 🎯 Usage Workflows

### Register New Customer with Vehicle

1. Admin Dashboard → **+ Register New Card**
2. Scan RFID card or enter UID manually
3. Fill in customer details:
   - Name, Email, Phone
   - **Vehicle Plate Number** (e.g., ABC-1234)
   - Department, Notes
4. Preview updates in real-time
5. Click **Register Card**
6. Card is now authorized for access

### Monitor Vehicles Inside

1. Admin Dashboard → **🚗 Vehicles Inside**
2. View real-time list of all vehicles currently inside
3. See for each vehicle:
   - Name & Vehicle Plate
   - Check-in time & Duration
   - Department & Card UID
4. Page auto-refreshes every 5 seconds
5. Vehicle count badge shows total

### Check-In/Out Process

**Automatic (via ESP32):**
1. Driver scans RFID card at gate
2. ESP32 validates card
3. If authorized → Boom barrier opens
4. Vehicle passes (ultrasonic sensor detects)
5. Boom barrier closes
6. Timestamp recorded in registry
7. WebSocket updates dashboard

**Manual (via Dashboard):**
1. Main Dashboard → Registry section
2. Enter Card UID
3. Click toggle button
4. Check-in or check-out timestamp added

### Export Reports to Excel

1. Admin Dashboard → **Audit Log**
2. Click **📥 Export Audit Log** (green button)
   - Downloads: `audit_log_YYYY-MM-DD_HHMMSS.csv`
   - Contains: Timestamps, UIDs, Auth status, Distance
3. Click **📊 Export Registry** (blue button)
   - Downloads: `registry_YYYY-MM-DD_HHMMSS.csv`
   - Contains: UID, Name, **Vehicle Plate**, Check-in/out, Duration

---

## 🔌 Hardware Setup

### Required Components

- ESP32 Development Board (ESP32-WROOM-32)
- MFRC522 RFID Reader Module (13.56 MHz)
- HC-SR04 Ultrasonic Distance Sensor
- SG90 Servo Motor (or similar)
- RFID Cards/Tags (13.56 MHz ISO 14443A)
- Breadboard and jumper wires
- 5V Power Supply

### Pin Connections

| Component | Pin | ESP32 GPIO |
|-----------|-----|------------|
| **MFRC522** | SDA | GPIO 5 |
| | SCK | GPIO 18 |
| | MOSI | GPIO 23 |
| | MISO | GPIO 19 |
| | RST | GPIO 22 |
| | 3.3V | 3.3V |
| **HC-SR04** | TRIG | GPIO 32 |
| | ECHO | GPIO 33 |
| | VCC | 5V |
| **Servo** | Signal | GPIO 25 |
| | VCC | 5V |
| **Common** | GND | GND |

**See [SETUP_AND_USAGE.md](SETUP_AND_USAGE.md) for detailed wiring diagram.**

---

## 🧪 Testing

### Automated Tests

Run verification script to check all files and implementations:

```cmd
cd Smart-Boom-Barrier-Dashboard-V2-main
verify.bat
```

**Expected Result:** All checks should pass ✅

### Manual Testing

Follow the comprehensive test plan with 25 test cases:

```cmd
# View test plan
notepad QA_TEST_PLAN.md
```

**Test Suites:**
1. Authentication & Admin Access
2. Customer Registration with Vehicle Plate
3. Check-In/Out Functionality
4. Real-Time Vehicles Display
5. Excel Export Functionality
6. Navigation & UI
7. Edge Cases & Error Handling
8. Performance & Reliability
9. Mobile Responsiveness

---

## 🛠️ Troubleshooting

### Common Issues

**PHP Server Issues:**
```cmd
# Check PHP installation
php -v

# Use different port if 8000 is busy
php -S localhost:8080
```

**ESP32 Not Connecting:**
- Verify WiFi credentials in firmware
- ESP32 only supports 2.4GHz WiFi (not 5GHz)
- Check firewall allows WebSocket port 81
- Ensure server IP is correct

**RFID Cards Not Detected:**
- Check wiring (especially SDA and RST pins)
- Use 3.3V for RFID module (not 5V)
- Verify cards are 13.56 MHz frequency
- Test with RFID example sketch first

**Vehicles Not Showing:**
- Ensure customer is registered first
- Check registry.json has 'in' timestamp without 'out'
- Wait for 5-second auto-refresh
- Check browser console (F12) for errors

**Excel Export Empty:**
- Verify registry.json has data
- Ensure logged in as admin
- Check PHP error logs
- Verify file permissions

**See [SETUP_AND_USAGE.md](SETUP_AND_USAGE.md) for detailed troubleshooting.**

---

## 🔒 Security

### Best Practices

✅ **Change default admin password** immediately after first login
✅ **Use HTTPS** in production (configure SSL certificate)
✅ **Local network only** - restrict external access with firewall
✅ **Regular backups** of customer database and registry
✅ **Update firmware** regularly for security patches

### Default Credentials

**⚠️ CHANGE THESE IMMEDIATELY:**
- Username: `admin`
- Password: `admin`

### Password Security

- Passwords hashed with **bcrypt** (cost factor 10)
- Session-based authentication with timeout
- No passwords stored in plain text

---

## 📊 Data Storage

All data stored in JSON format at `data/data/`:

| File | Purpose | Format |
|------|---------|--------|
| `users.json` | Admin accounts | JSON object |
| `tags.json` | Authorized UIDs | JSON array |
| `registry.json` | Check-in/out records | JSON object |
| `customers.json` | Customer & vehicle data | JSON object |
| `esp32_audit.log` | Access attempt logs | NDJSON |

### Backup Strategy

```cmd
# Create backup
mkdir backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%
xcopy data\data\*.* backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%\ /s
```

**Recommended:**
- Daily automated backups
- Weekly full system backups
- Test restore procedures monthly

---

## 🚀 Deployment

### Development Server (Current)

```cmd
php -S localhost:8000
```

**Use for:** Testing, development, local network only

### Production Server (Recommended)

**Option 1: XAMPP/WAMP**
1. Install XAMPP
2. Copy files to `htdocs/`
3. Configure Apache virtual host
4. Enable HTTPS with SSL certificate

**Option 2: Nginx + PHP-FPM**
1. Install Nginx and PHP-FPM
2. Configure Nginx server block
3. Set up SSL/TLS certificates
4. Enable firewall rules

**Option 3: Cloud Hosting**
- Use VPS (DigitalOcean, Linode, AWS EC2)
- Configure PHP web server
- Set up domain with SSL
- Implement VPN for secure access

---

## 📝 API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api.php?action=register_rfid` | POST | Yes | Register new customer with vehicle plate |
| `/api.php?action=get_customers` | POST | Yes | Get all registered customers |
| `/api.php?action=get_vehicles_inside` | POST | Yes | Get vehicles currently inside |
| `/api.php?action=export_registry_excel` | GET | Yes | Export registry to CSV |
| `/api.php?action=export_audit_excel` | GET | Yes | Export audit log to CSV |

**Authentication:** Session-based (admin role required)

---

## 🎨 Customization

### Change Server Port

```cmd
php -S localhost:9000
```

Update ESP32 firmware `serverPort` variable accordingly.

### Modify Auto-Refresh Interval

Edit `vehicles_inside.html`:
```javascript
let refreshInterval = 5000; // Change to desired milliseconds
```

### Customize Servo Angles

Edit ESP32 firmware:
```cpp
servo.write(0);   // Closed position (0-180)
servo.write(90);  // Open position (0-180)
```

### Add Custom Fields

1. Modify `register_rfid.html` form
2. Update `api.php` register_rfid endpoint
3. Update `customers.json` structure
4. Update display pages accordingly

---

## 📈 System Capabilities

- **Concurrent Users:** 10-20 (PHP dev server)
- **Max Registered Cards:** Unlimited (JSON storage)
- **Audit Log Size:** Limited by disk space
- **WebSocket Clients:** 1 ESP32 device
- **Auto-Refresh Rate:** 5 seconds (configurable)
- **RFID Read Range:** 3-5 cm (depending on card/reader)
- **Ultrasonic Range:** 2-400 cm

---

## 🤝 Contributing

This is a closed project for educational/internal use. For suggestions:

1. Document your proposed changes
2. Test thoroughly with QA test plan
3. Ensure backward compatibility
4. Update relevant documentation

---

## 📄 License

Proprietary - For authorized use only.

---

## 🙏 Acknowledgments

- **MFRC522 Library** - RFID reader interface
- **ESP32 Arduino Core** - ESP32 development framework
- **PHP** - Backend server
- **WebSocket Protocol** - Real-time communication

---

## 📞 Support

**Documentation:**
- [SETUP_AND_USAGE.md](SETUP_AND_USAGE.md) - Complete user manual
- [QA_TEST_PLAN.md](QA_TEST_PLAN.md) - Testing procedures
- [AUTOMATED_TEST_RESULTS.md](AUTOMATED_TEST_RESULTS.md) - Verification results

**Troubleshooting:**
- Check browser console (F12) for JavaScript errors
- Check PHP error logs for server-side issues
- Monitor ESP32 serial output (115200 baud)
- Review troubleshooting section in setup guide

---

## 📅 Version History

### Version 2.0 (2025-12-02) - Current
✨ Added vehicle plate number tracking
✨ Added real-time vehicles inside display with auto-refresh
✨ Added Excel export functionality for audit logs and registry
✨ Improved UI with real-time preview
✨ Enhanced admin dashboard navigation
✨ Created comprehensive documentation

### Version 1.0 (Previous)
- Basic RFID access control
- Customer registration
- Check-in/out tracking
- Admin dashboard
- WebSocket integration with ESP32

---

<div align="center">

**ESP32 RFID Customer Access System V2.0**

🚗 Track • 📊 Monitor • 🔒 Secure

Made with ❤️ for vehicle access control

</div>
