$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backendPath = Join-Path $root 'api-main\laravel-api'
$frontendPath = Join-Path $root 'login-router-main\login_page'
$nodePath = Join-Path $root 'tools\node-v24.14.1-win-x64'
$phpExe = 'C:\xampp\php\php.exe'
$powershellExe = 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe'

if (!(Test-Path $backendPath)) {
    throw "Backend path not found: $backendPath"
}

if (!(Test-Path $frontendPath)) {
    throw "Frontend path not found: $frontendPath"
}

if (!(Test-Path $phpExe)) {
    throw "PHP executable not found at $phpExe"
}

if (!(Test-Path (Join-Path $nodePath 'npm.cmd'))) {
    throw "Local npm not found in $nodePath"
}

$backendCommand = @"
Set-Location '$backendPath'
& '$phpExe' artisan serve --host=127.0.0.1 --port=8000
"@

$frontendCommand = @"
Set-Location '$frontendPath'
\$env:PATH='$nodePath;' + \$env:PATH
& '$nodePath\npm.cmd' run dev -- --host 127.0.0.1 --port 5173
"@

Start-Process -FilePath $powershellExe -ArgumentList '-NoExit', '-Command', $backendCommand
Start-Process -FilePath $powershellExe -ArgumentList '-NoExit', '-Command', $frontendCommand

Write-Host 'Backend:  http://127.0.0.1:8000'
Write-Host 'Frontend: http://127.0.0.1:5173'
Write-Host ''
Write-Host 'Open the frontend URL in the browser for the React app.'
Write-Host 'Port 8000 is the Laravel API/backend helper page, not the main UI.'
