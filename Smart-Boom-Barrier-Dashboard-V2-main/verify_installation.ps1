# ESP32 RFID System - Installation Verification Script
# Run this with: powershell -ExecutionPolicy Bypass -File verify_installation.ps1

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "ESP32 RFID System - Verification Script" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$errors = @()
$warnings = @()
$passed = 0
$total = 0

function Test-File {
    param($path, $description)
    $global:total++
    if (Test-Path $path) {
        Write-Host "[✓] $description" -ForegroundColor Green
        $global:passed++
        return $true
    } else {
        Write-Host "[✗] $description - NOT FOUND" -ForegroundColor Red
        $global:errors += "$description (File: $path)"
        return $false
    }
}

function Test-FileContent {
    param($path, $pattern, $description)
    $global:total++
    if (Test-Path $path) {
        $content = Get-Content $path -Raw
        if ($content -match $pattern) {
            Write-Host "[✓] $description" -ForegroundColor Green
            $global:passed++
            return $true
        } else {
            Write-Host "[✗] $description - PATTERN NOT FOUND" -ForegroundColor Red
            $global:errors += "$description (File: $path, Pattern: $pattern)"
            return $false
        }
    } else {
        Write-Host "[✗] $description - FILE NOT FOUND" -ForegroundColor Red
        $global:errors += "$description (File: $path)"
        return $false
    }
}

Write-Host "1. Checking Critical Files..." -ForegroundColor Yellow
Write-Host "------------------------------`n"

# Check backend files
Test-File "data\api.php" "Backend API exists"
Test-File "data\backend.js" "Backend helper exists"
Test-File "data\auth.js" "Authentication module exists"

# Check new files
Test-File "data\vehicles_inside.html" "Real-time vehicles display exists (NEW)"
Test-File "data\data\customers.json" "Customer registry exists (NEW)"

# Check modified files
Test-File "data\register_rfid.html" "Registration form exists"
Test-File "data\admin.html" "Admin dashboard exists"
Test-File "data\admin_audit.php" "Audit viewer exists"

Write-Host "`n2. Checking Vehicle Plate Implementation..." -ForegroundColor Yellow
Write-Host "--------------------------------------------`n"

# Check for vehicle plate in registration form
Test-FileContent "data\register_rfid.html" "vehiclePlate" "Vehicle plate field in registration form"
Test-FileContent "data\register_rfid.html" "previewPlate" "Vehicle plate in preview section"

# Check for vehicle plate in API
Test-FileContent "data\api.php" "vehicle_plate" "Vehicle plate handling in API"
Test-FileContent "data\api.php" "register_rfid" "register_rfid endpoint exists"
Test-FileContent "data\api.php" "get_vehicles_inside" "get_vehicles_inside endpoint exists"
Test-FileContent "data\api.php" "export_registry_excel" "Excel export endpoint exists"

# Check for new backend methods
Test-FileContent "data\backend.js" "getCustomers" "getCustomers method in backend.js"
Test-FileContent "data\backend.js" "getVehiclesInside" "getVehiclesInside method in backend.js"

Write-Host "`n3. Checking UI Updates..." -ForegroundColor Yellow
Write-Host "-------------------------`n"

# Check for Vehicles Inside button
Test-FileContent "data\admin.html" "vehicles_inside.html" "Vehicles Inside link in admin dashboard"

# Check for export buttons
Test-FileContent "data\admin_audit.php" "export_audit_excel" "Export Audit button exists"
Test-FileContent "data\admin_audit.php" "export_registry_excel" "Export Registry button exists"

Write-Host "`n4. Checking PHP Version..." -ForegroundColor Yellow
Write-Host "--------------------------`n"

$global:total++
try {
    $phpVersion = php -v 2>&1 | Select-String "PHP (\d+\.\d+)" | ForEach-Object { $_.Matches.Groups[1].Value }
    if ($phpVersion) {
        Write-Host "[✓] PHP version: $phpVersion" -ForegroundColor Green
        $global:passed++

        $versionNum = [double]$phpVersion
        if ($versionNum -lt 7.4) {
            $global:warnings += "PHP version $phpVersion is old. PHP 7.4+ recommended."
            Write-Host "    [!] Warning: PHP 7.4+ recommended" -ForegroundColor Yellow
        }
    } else {
        Write-Host "[✗] PHP version check failed" -ForegroundColor Red
        $global:errors += "Could not determine PHP version"
    }
} catch {
    Write-Host "[✗] PHP not found in PATH" -ForegroundColor Red
    $global:errors += "PHP not installed or not in PATH"
}

Write-Host "`n5. Validating JSON Structure..." -ForegroundColor Yellow
Write-Host "-------------------------------`n"

$global:total++
try {
    $customersContent = Get-Content "data\data\customers.json" -Raw
    $customersJson = ConvertFrom-Json $customersContent
    Write-Host "[✓] customers.json is valid JSON" -ForegroundColor Green
    $global:passed++
} catch {
    Write-Host "[✗] customers.json is invalid JSON" -ForegroundColor Red
    $global:errors += "customers.json JSON validation failed"
}

Write-Host "`n6. Testing Server Startup..." -ForegroundColor Yellow
Write-Host "----------------------------`n"

$global:total++
Write-Host "Starting PHP server on localhost:8000..." -ForegroundColor Cyan

$serverJob = Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8000", "-t", "data" -PassThru -WindowStyle Hidden

Start-Sleep -Seconds 3

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api.php?action=ping" -UseBasicParsing -TimeoutSec 5
    $responseData = $response.Content | ConvertFrom-Json

    if ($responseData.ok -eq $true -and $responseData.msg -eq "pong") {
        Write-Host "[✓] Server responds correctly" -ForegroundColor Green
        Write-Host "    Response: $($response.Content)" -ForegroundColor Gray
        $global:passed++
    } else {
        Write-Host "[✗] Server response invalid" -ForegroundColor Red
        $global:errors += "Server ping response invalid"
    }
} catch {
    Write-Host "[✗] Server not responding" -ForegroundColor Red
    $global:warnings += "Could not connect to server. Make sure port 8000 is available."
}

# Stop the test server
Stop-Process -Id $serverJob.Id -Force -ErrorAction SilentlyContinue

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "VERIFICATION SUMMARY" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$passRate = [math]::Round(($passed / $total) * 100, 2)

Write-Host "Total Tests: $total" -ForegroundColor White
Write-Host "Passed: $passed " -NoNewline
if ($passed -eq $total) {
    Write-Host "✓" -ForegroundColor Green
} else {
    Write-Host "✗" -ForegroundColor Yellow
}
Write-Host "Failed: $($total - $passed)" -ForegroundColor $(if ($total -eq $passed) { "Green" } else { "Red" })
Write-Host "Pass Rate: $passRate%" -ForegroundColor $(if ($passRate -eq 100) { "Green" } elseif ($passRate -ge 80) { "Yellow" } else { "Red" })

if ($errors.Count -gt 0) {
    Write-Host "`n❌ ERRORS FOUND:" -ForegroundColor Red
    foreach ($error in $errors) {
        Write-Host "  - $error" -ForegroundColor Red
    }
}

if ($warnings.Count -gt 0) {
    Write-Host "`n⚠️  WARNINGS:" -ForegroundColor Yellow
    foreach ($warning in $warnings) {
        Write-Host "  - $warning" -ForegroundColor Yellow
    }
}

Write-Host "`n========================================`n" -ForegroundColor Cyan

if ($passed -eq $total) {
    Write-Host "✅ ALL CHECKS PASSED!" -ForegroundColor Green
    Write-Host "`nYou can now start the server with:" -ForegroundColor Cyan
    Write-Host "  cd data && php -S localhost:8000`n" -ForegroundColor White
    exit 0
} elseif ($passRate -ge 80) {
    Write-Host "⚠️  MOSTLY WORKING" -ForegroundColor Yellow
    Write-Host "Some issues found but system should be functional.`n" -ForegroundColor Yellow
    exit 1
} else {
    Write-Host "❌ CRITICAL ISSUES FOUND" -ForegroundColor Red
    Write-Host "Please fix the errors above before proceeding.`n" -ForegroundColor Red
    exit 2
}
