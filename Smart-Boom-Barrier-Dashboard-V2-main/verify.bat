@echo off
echo ========================================
echo ESP32 RFID System - Quick Verification
echo ========================================
echo.

cd "Smart-Boom-Barrier-Dashboard-V2-main"

echo [1] Checking critical files...
echo.

if exist "data\api.php" (
    echo [OK] api.php exists
) else (
    echo [FAIL] api.php missing
)

if exist "data\vehicles_inside.html" (
    echo [OK] vehicles_inside.html exists - NEW FILE
) else (
    echo [FAIL] vehicles_inside.html missing
)

if exist "data\data\customers.json" (
    echo [OK] customers.json exists - NEW FILE
) else (
    echo [FAIL] customers.json missing
)

if exist "data\register_rfid.html" (
    echo [OK] register_rfid.html exists
) else (
    echo [FAIL] register_rfid.html missing
)

if exist "data\admin.html" (
    echo [OK] admin.html exists
) else (
    echo [FAIL] admin.html missing
)

if exist "data\backend.js" (
    echo [OK] backend.js exists
) else (
    echo [FAIL] backend.js missing
)

echo.
echo [2] Checking PHP installation...
echo.

php -v >nul 2>&1
if %errorlevel%==0 (
    echo [OK] PHP is installed
    php -v | findstr /C:"PHP"
) else (
    echo [FAIL] PHP not found
)

echo.
echo [3] Checking file modifications...
echo.

findstr /C:"vehicle_plate" "data\api.php" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] vehicle_plate in api.php
) else (
    echo [FAIL] vehicle_plate not found in api.php
)

findstr /C:"vehiclePlate" "data\register_rfid.html" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] vehiclePlate field in register_rfid.html
) else (
    echo [FAIL] vehiclePlate not found in register_rfid.html
)

findstr /C:"vehicles_inside" "data\admin.html" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] vehicles_inside link in admin.html
) else (
    echo [FAIL] vehicles_inside link not found
)

findstr /C:"getVehiclesInside" "data\backend.js" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] getVehiclesInside method in backend.js
) else (
    echo [FAIL] getVehiclesInside not found
)

echo.
echo ========================================
echo Verification Complete
echo ========================================
echo.
echo To start the server, run:
echo   cd data
echo   php -S localhost:8000
echo.
echo Then open: http://localhost:8000/login.html
echo.

pause
