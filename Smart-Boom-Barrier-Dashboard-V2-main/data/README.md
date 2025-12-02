# Boom Barrier RFID Dashboard

Static dashboard that simulates RFID tag reads and a boom barrier animation.

Files added:
- `index.html` — main page
- `main.css` — styling
- `main.js` — behavior: simulate scans, authorize tags, animate barrier

How to open
1. Open `index.html` in your browser (double-click or right-click -> Open With).
2. Use the input or quick tags to simulate an RFID read. Authorized tags: `TAG-D9BBB311`, `TAG-D9BBB311`, `TAG-1003`, `EMP-42`.

Notes
- This is a front-end-only simulation. To connect a real RFID reader you would POST scan events to a backend and update the allowed list dynamically.

Authentication and Admin
- A simple front-end auth system is included (NOT secure, for demo purposes only).
- Default admin account: `admin` / `admin` (created automatically).
- Use `Register` to create users. Assign `admin` role to allow managing allowed tags.
- Admin page (`admin.html`) lets you add/remove allowed tags. Changes are saved in localStorage and reflected immediately in the dashboard.

Run with PHP (optional)
If you prefer a tiny PHP-backed demo (stores users, tags and registry on disk), you can run the built-in PHP server from the `c:\Dashboard` folder:

```powershell
# start PHP server on port 8000
php -S localhost:8000 -t .\
```

Then open http://localhost:8000/index.html in your browser. The API is available at `/api.php` and supports actions such as `login`, `register`, `get_tags`, `add_tag`, `get_registry`, and `toggle_registry`.

Security note: the PHP API included is a simple file-backed demo and not hardened for production. Use only for local testing.

ESP32 / device integration
- `api.php` accepts device requests for `toggle_registry` when a correct device key is provided (query or POST param `device_key` or `api_key`). The demo key in `api.php` is `esp32-demo-key` — change it before deploying.
- To make your PHP server reachable by the ESP32 on your LAN, run the built-in server bound to all interfaces:

```powershell
php -S 0.0.0.0:8000 -t .\
```

- See `esp32_example.md` for a short Arduino/ESP32 sketch that posts tag reads to the API.

ESP32 POST endpoint
-------------------

The project now includes a lightweight endpoint `esp32_post.php` that accepts POST requests from an ESP32 or other device. The endpoint expects an `api_key` POST field to authorize the request.

Example `curl` (replace the API key):

```powershell
curl -X POST http://<HOST>/esp32_post.php -d "api_key=REPLACE_WITH_SECRET" -d "uid=TAG-1001" -d "authorized=1" -d "distance=42"
```

Expected JSON response on success:

```json
{
	"status": "success",
	"data": {
		"uid": "TAG-1001",
		"authorized": true,
		"distance": 42,
		"timestamp": "2025-11-20 12:34:56"
	}
}
```

Notes:
- Edit `esp32_post.php` and set `$EXPECTED_API_KEY` to a secure value before using on a network.
- The endpoint writes the latest POST payload to `data/esp32.json` using an exclusive lock.

Audit trail
-----------

Each POST to `esp32_post.php` now appends a JSON-line to `data/esp32_audit.log`. Each line contains a minimal audit entry:

```json
{
	"ip": "192.168.1.42",
	"uid": "TAG-1001",
	"authorized": true,
	"distance": 42,
	"timestamp": "2025-11-20 12:34:56",
	"status": "success",
	"http_code": 200
}
```

The audit file is a newline-delimited JSON file (`NDJSON`). It intentionally omits the `api_key` value to avoid storing secrets.

Configuration and admin viewer
--------------------------------

Database setup for RFID scan logging
------------------------------------

To log every RFID scan to a MySQL database, create the following table (example for MySQL):

```sql
CREATE TABLE rfid_scans (
	id INT AUTO_INCREMENT PRIMARY KEY,
	uid VARCHAR(64) NOT NULL,
	authorized TINYINT(1) NOT NULL,
	distance INT DEFAULT NULL,
	ip VARCHAR(45) DEFAULT NULL,
	scan_time DATETIME NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Edit `config.php` and set your MySQL credentials:

```php
'DB_HOST' => 'localhost',
'DB_NAME' => 'dashboard',
'DB_USER' => 'root',
'DB_PASS' => '',
'DB_CHARSET' => 'utf8mb4',
```

The endpoint will insert each scan into this table if the DB connection is configured.

The `config.php` file contains the `ESP32_API_KEY` and audit rotation settings. Edit `config.php` and set `ESP32_API_KEY` to a secure string before deploying.

You can view recent audit entries in the browser with `admin_audit.php`:

Open:

http://<HOST>/admin_audit.php

Optional query parameters:
- `?limit=100` — number of recent entries to show (default 200)
- `?search=TAG-1001` — filter by UID or IP substring

Rotated audit files are placed in `data/audit_archive` (configurable via `config.php`).
