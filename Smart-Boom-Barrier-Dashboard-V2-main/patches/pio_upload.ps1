<#
PlatformIO upload helper
Usage:
  powershell -ExecutionPolicy Bypass -File .\patches\pio_upload.ps1
Options:
  -Port COM3    : optional, specify upload serial port (COM port) e.g. COM3
  -SkipFS       : optional switch to skip LittleFS upload
  -MonitorOnly  : optional switch to only open the serial monitor
#>

param(
  [string]$Port = "",
  [switch]$SkipFS,
  [switch]$MonitorOnly
)

$projRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition | Resolve-Path -Relative -ErrorAction Stop
Set-Location -Path "$PSScriptRoot\.."

function RunCmd($cmd) {
  Write-Output "-> $cmd"
  & cmd /c $cmd
  if ($LASTEXITCODE -ne 0) { throw "Command failed with exit code $LASTEXITCODE" }
}

try {
  if ($MonitorOnly) {
    if ($Port -ne "") {
      RunCmd "pio device monitor -e esp32dev -p $Port"
    } else {
      RunCmd "pio device monitor -e esp32dev"
    }
    exit 0
  }

  # Build
  RunCmd "pio run -e esp32dev"

  # Upload firmware
  if ($Port -ne "") {
    RunCmd "pio run -e esp32dev -t upload --upload-port $Port"
  } else {
    RunCmd "pio run -e esp32dev -t upload"
  }

  # Upload filesystem (LittleFS) unless skipped
  if (-not $SkipFS) {
    if (Test-Path data) {
      if ($Port -ne "") {
        RunCmd "pio run -e esp32dev -t uploadfs --upload-port $Port"
      } else {
        RunCmd "pio run -e esp32dev -t uploadfs"
      }
    } else {
      Write-Output "No data/ folder found — skipping uploadfs. Create a data/ folder with index.html to upload LittleFS."
    }
  }

  # Open serial monitor
  if ($Port -ne "") {
    RunCmd "pio device monitor -e esp32dev -p $Port"
  } else {
    RunCmd "pio device monitor -e esp32dev"
  }

} catch {
  Write-Error "Error: $_"
  exit 1
}
