$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
$ConfigDir = Join-Path $Root 'config'
$PasswdExe = 'C:\Program Files\mosquitto\mosquitto_passwd.exe'
$PasswordFile = Join-Path $ConfigDir 'password.txt'
$AclTemplate = Join-Path $ConfigDir 'acl.txt.example'
$AclFile = Join-Path $ConfigDir 'acl.txt'

if (-not (Test-Path $PasswdExe)) {
    throw "mosquitto_passwd.exe tidak ditemukan di '$PasswdExe'. Cek hasil where.exe mosquitto_passwd dan ubah path di script ini jika instalasinya berbeda."
}
if (-not (Test-Path $AclTemplate)) {
    throw "Template ACL tidak ditemukan: $AclTemplate"
}
if (Test-Path $PasswordFile) {
    throw "File password sudah ada; tidak ditimpa demi keamanan: $PasswordFile. Backup dan atur akun secara manual, atau ubah nama file baru dengan sengaja."
}
if (-not (Test-Path $AclFile)) {
    Copy-Item $AclTemplate $AclFile
}

Write-Host 'Membuat akun vending_backend. Masukkan password saat diminta.' -ForegroundColor Cyan
& $PasswdExe -c $PasswordFile 'vending_backend'
if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat akun vending_backend.' }

Write-Host 'Menambahkan akun vending_simulator_3. Masukkan password saat diminta.' -ForegroundColor Cyan
& $PasswdExe $PasswordFile 'vending_simulator_3'
if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat akun vending_simulator_3.' }

Write-Host "Selesai. File password: $PasswordFile" -ForegroundColor Green
Write-Host 'Jangan commit password.txt ke GitHub. Berikan kredensial yang berbeda kepada setiap client.' -ForegroundColor Yellow
