# apply_asyncwebserver_fix.ps1
# Backs up and patches ESPAsyncWebServer.h to avoid const mismatch with AsyncTCP
# Usage: powershell -ExecutionPolicy Bypass -File .\apply_asyncwebserver_fix.ps1

$possiblePaths = @(
    'C:\c\libraries\ESP_Async_WebServer\src\ESPAsyncWebServer.h',
    "$env:USERPROFILE\\Documents\\Arduino\\libraries\\ESPAsyncWebServer\\src\\ESPAsyncWebServer.h",
    'C:\Users\nathaniel\AppData\Local\Arduino15\libraries\ESPAsyncWebServer\src\ESPAsyncWebServer.h'
)

$found = $null
foreach ($p in $possiblePaths) {
    if (Test-Path $p) { $found = $p; break }
}

if (-not $found) {
    Write-Error "ESPAsyncWebServer.h not found in expected locations. Check your library paths and update the script."
    exit 1
}

$ts = Get-Date -Format 'yyyyMMddHHmmss'
$bak = "$found.bak.$ts"
Write-Output "Backing up $found -> $bak"
Copy-Item -Path $found -Destination $bak -Force

# Regex to find the state() const method that calls _server.status()
$pattern = 'tcp_state\s+state\(\)\s+const\s*\{\s*return\s+static_cast<tcp_state>\(_server.status\(\)\);\s*\}'
$replacement = 'tcp_state state() const { return static_cast<tcp_state>(const_cast<AsyncServer&>(_server).status()); }'

$content = Get-Content $found -Raw
if ($content -match $pattern) {
    $new = [regex]::Replace($content, $pattern, [System.Text.RegularExpressions.Regex]::Escape($replacement))
    # The above escapes replacement which would otherwise be taken literally; we want the plain replacement string
    # So do a manual replace of the escaped backslashes
    $new = [regex]::Replace($content, $pattern, $replacement)
    Set-Content -Path $found -Value $new -Encoding UTF8
    Write-Output "Patched $found successfully."
} else {
    Write-Warning "Pattern not found. The file may already be patched or different version. No changes made."
}

Write-Output "Done. Rebuild your sketch in Arduino IDE."