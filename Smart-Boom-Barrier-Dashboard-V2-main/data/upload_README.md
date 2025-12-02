ESP32 File Upload - Quick Instructions

1) Where to put the sketch
- Place `esp32_upload.ino` into your PlatformIO project's `src/` folder (or open it in the Arduino IDE).
- Make sure `platformio.ini` includes `board_build.filesystem = littlefs` (your file shows this already).

2) Libraries required
- `ESP Async WebServer`
- `AsyncTCP`
- `lorol/LittleFS` (or equivalent LittleFS for ESP32)

3) Configure Wi‑Fi
- Edit `esp32_upload.ino` and set `ssid` and `password` to your network credentials.

4) Build & upload
PlatformIO (from project root):
```powershell
# build
pio run
# upload to board (make sure correct port & env)
pio run -t upload
```
Or open the sketch in the Arduino IDE and upload.

5) Test the upload UI
- Open the serial monitor at `115200` to see IP address printed after Wi‑Fi connects.
- In a browser go to `http://<ESP_IP>/upload`, choose a file and submit.
- Uploaded files are served at `http://<ESP_IP>/fs/<filename>`.
- List files: `http://<ESP_IP>/list` (returns JSON array).

6) Quick curl examples (Windows PowerShell)
Use `curl.exe` explicitly to avoid PowerShell alias issues:
```powershell
curl.exe -F "file=@C:\path\to\localfile.bin" http://<ESP_IP>/upload
curl.exe http://<ESP_IP>/fs/localfile.bin -o downloaded.bin
curl.exe http://<ESP_IP>/list
```

Notes
- LittleFS size depends on partition scheme; large files may not fit.
- The example overwrites files with the same name. Add logic to rename if needed.
- If LittleFS fails to mount, the sketch formats it once automatically.

AP mode

If you don't want to join an existing Wi‑Fi network, use the AP-mode sketch `esp32_upload_ap.ino`.
- By default the soft-AP SSID is `ESP32-Upload` and the AP IP is `192.168.4.1`.
- Connect a laptop/phone to that SSID, then open `http://192.168.4.1/upload` in a browser to use the upload UI.
- To require a password for the AP, edit `ap_password` in the sketch (must be 8+ characters for WPA2).

Example quick test when connected to the ESP AP:
```powershell
# upload a file (from host connected to ESP AP)
curl.exe -F "file=@C:\path\to\localfile.bin" http://192.168.4.1/upload
# list files
curl.exe http://192.168.4.1/list
# retrieve a file
curl.exe http://192.168.4.1/fs/localfile.bin -o from_esp.bin
```

Delete files

You can delete an uploaded file by POSTing the `file` field to `/delete`. Example:
```powershell
curl.exe -X POST -F "file=localfile.bin" http://192.168.4.1/delete
```

Files are stored under the `"/uploads"` folder in LittleFS; the server exposes them via `/fs/<filename>`.
