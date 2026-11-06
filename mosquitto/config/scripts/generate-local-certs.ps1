$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
$CertDir = Join-Path $Root 'certs'
New-Item -ItemType Directory -Force -Path $CertDir | Out-Null

$OpenSSL = Get-Command openssl -ErrorAction SilentlyContinue
if (-not $OpenSSL) {
    throw 'OpenSSL belum ditemukan di PATH. Instal OpenSSL atau gunakan OpenSSL yang tersedia lewat Git for Windows, lalu buka PowerShell baru.'
}

$CaKey = Join-Path $CertDir 'ca.key'
$CaCert = Join-Path $CertDir 'ca.crt'
$ServerKey = Join-Path $CertDir 'server.key'
$ServerCsr = Join-Path $CertDir 'server.csr'
$ServerCert = Join-Path $CertDir 'server.crt'
$Serial = Join-Path $CertDir 'ca.srl'
$OpenSslConfig = Join-Path $CertDir 'server-cert.cnf'

foreach ($p in @($CaKey, $CaCert, $ServerKey, $ServerCert)) {
    if (Test-Path $p) { throw "File sertifikat sudah ada, script berhenti tanpa menimpa: $p. Backup/hapus hanya jika yakin ingin membuat ulang." }
}

@'
[ req ]
prompt = no
default_md = sha256
distinguished_name = dn
req_extensions = v3_req
[ dn ]
CN = localhost
[ v3_req ]
basicConstraints = critical,CA:FALSE
keyUsage = critical,digitalSignature,keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names
[ alt_names ]
DNS.1 = localhost
IP.1 = 127.0.0.1
'@ | Set-Content -Encoding ascii $OpenSslConfig

Write-Host 'Membuat CA lokal. CA private key hanya untuk pengembangan lokal; jangan unggah ke GitHub.' -ForegroundColor Cyan
& openssl genrsa -out $CaKey 4096
if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat CA private key.' }
& openssl req -x509 -new -nodes -key $CaKey -sha256 -days 3650 -out $CaCert -subj '/CN=Vending MQTT Local CA' -addext 'basicConstraints=critical,CA:TRUE' -addext 'keyUsage=critical,keyCertSign,cRLSign'
if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat CA certificate. Jika OpenSSL terlalu lama dan tidak mengenal -addext, perbarui OpenSSL lalu ulangi setelah mengamankan/menghapus file hasil parsial.' }

Write-Host 'Membuat sertifikat server untuk localhost / 127.0.0.1.' -ForegroundColor Cyan
& openssl genrsa -out $ServerKey 2048
if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat server key.' }
& openssl req -new -key $ServerKey -out $ServerCsr -config $OpenSslConfig
if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat CSR.' }
& openssl x509 -req -in $ServerCsr -CA $CaCert -CAkey $CaKey -CAcreateserial -out $ServerCert -days 825 -sha256 -extfile $OpenSslConfig -extensions v3_req
if ($LASTEXITCODE -ne 0) { throw 'Gagal menandatangani sertifikat server.' }

Write-Host 'Sertifikat lokal berhasil dibuat:' -ForegroundColor Green
Write-Host "  CA:         $CaCert"
Write-Host "  Server cert:$ServerCert"
Write-Host "  Server key: $ServerKey"
Write-Host 'Simpan ca.crt di sisi client untuk memverifikasi broker. Jangan pernah membagikan ca.key atau server.key.' -ForegroundColor Yellow
