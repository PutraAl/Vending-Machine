# Paket setup MQTT aman — Vending Machine (Windows local dev)

Paket ini membuat **profil Mosquitto kedua yang khusus untuk pengujian lokal**. Profil ini tidak mengubah `C:\Program Files\mosquitto\mosquitto.conf` dan tidak membuka broker ke LAN/internet. Ia memakai TLS di `localhost:8883`, autentikasi password, dan ACL pada topic yang sebelumnya dibahas.

> Penting: topic dalam `config/acl.txt.example` adalah draft berdasarkan percakapan sebelumnya (`vending/machine/command`, `status`, `telemetry`, `error`, `temperature`, `stock`, `door`). Cocokkan dengan kode aktual `mqtt/client.py`, publisher, subscriber, dan `main.py` sebelum mengandalkan ACL. ACL yang tidak cocok bisa membuat publish/subscribe sah ditolak. Jangan ganti nama topic aplikasi hanya untuk menyesuaikan contoh ini.

## Kenapa profile ini local-only?

Mosquitto ada di laptop Windows, sementara Laravel berada di Vercel dan perangkat kelak bisa berada di jaringan lain. Laptop tidak selalu online dan biasanya berada di balik NAT/router. Jangan membuka port router ke laptop untuk produksi. Setelah tes lokal lulus, pindahkan broker ke VPS atau layanan MQTT terkelola dengan hostname publik dan sertifikat TLS yang valid.

## 0. Prasyarat dan backup

1. Cek versi dan lokasi program:
   ```powershell
   where.exe mosquitto
   where.exe mosquitto_passwd
   where.exe mosquitto_pub
   where.exe mosquitto_sub
   mosquitto -h
   ```
2. Jangan mengedit atau mengganti konfigurasi service Mosquitto lama. Profil baru memakai port 8883 dan bind ke `127.0.0.1` saja.
3. Jika sudah memiliki folder `C:\vending-mqtt`, backup terlebih dahulu. Ekstrak isi paket ke `C:\vending-mqtt` (folder `config`, `scripts`, `python-test` berada langsung di dalamnya).
4. Jika Mosquitto tidak terpasang di `C:\Program Files\mosquitto`, ubah path `mosquitto_passwd.exe` di `scripts\create-users.ps1` sesuai hasil `where.exe`.

## 1. Buat direktori data dan log

Jalankan PowerShell:

```powershell
New-Item -ItemType Directory -Force C:\vending-mqtt\data | Out-Null
New-Item -ItemType Directory -Force C:\vending-mqtt\logs | Out-Null
New-Item -ItemType Directory -Force C:\vending-mqtt\certs | Out-Null
```

## 2. Buat sertifikat TLS khusus lokal

Script membutuhkan OpenSSL di `PATH`:

```powershell
openssl version
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\generate-local-certs.ps1
```

Perintah dijalankan dari `C:\vending-mqtt`. Script hanya membuat sertifikat jika file tujuan belum ada. Sertifikat ini hanya untuk `localhost`/`127.0.0.1`, bukan sertifikat internet.

- `certs\ca.crt`: certificate publik CA lokal, boleh disalin ke klien yang perlu memercayai broker.
- `certs\ca.key`: private key CA, rahasiakan dan jangan deploy/commit.
- `certs\server.key`: private key broker, rahasiakan.
- `certs\server.crt`: sertifikat server broker.

Batasi akses folder `certs`; jangan mengirim `ca.key` atau `server.key` ke GitHub, chat, atau orang lain.

## 3. Buat akun dan ACL

Dari `C:\vending-mqtt`:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\create-users.ps1
```

Masukkan password kuat saat diminta. Akun yang dibuat:

- `vending_backend`: hanya boleh menulis `vending/machine/command` dan membaca topic telemetri/status.
- `vending_simulator_3`: hanya boleh membaca command dan menulis topic telemetri/status.

Script berhenti jika `password.txt` sudah ada agar file lama tidak tertimpa. Tinjau `config\acl.txt` dan pastikan daftar topic benar-benar cocok dengan kode aktual. Tambahkan hak akses hanya seperlunya. File `password.txt` tidak boleh masuk repository.

## 4. Jalankan broker lokal di console terpisah

Pastikan proses `mosquitto` berada pada folder instalasi. Buka PowerShell kedua dan jalankan:

```powershell
& 'C:\Program Files\mosquitto\mosquitto.exe' -c 'C:\vending-mqtt\config\mosquitto-local-tls.conf' -v
```

Jika instalasi di lokasi lain, ganti path executable. Biarkan console itu hidup selama tes. Ini proses broker terpisah, tidak mengubah service yang sudah ada. Bila error, jangan lanjut ke Laravel; perbaiki pesan error terlebih dahulu.

## 5. Uji koneksi TLS dengan Python tanpa mengirim command

Buka PowerShell baru:

```powershell
cd C:\vending-mqtt\python-test
Copy-Item .env.example .env
py -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
python mqtt_tls_smoke_test.py
```

Script akan meminta password secara tersembunyi jika `MQTT_PASSWORD` kosong. Ia **hanya subscribe** dan tidak publish atau mengeksekusi dispense. Ia memakai `ca.crt` untuk memverifikasi sertifikat dan tidak menonaktifkan verifikasi hostname.

Jika koneksi berhasil tetapi subscription ditolak, periksa `config\acl.txt` dan log broker. Jika sertifikat ditolak, jangan matikan verifikasi TLS; pastikan `MQTT_HOST=localhost`, `MQTT_CA_CERT=C:/vending-mqtt/certs/ca.crt`, dan sertifikat yang dibuat berasal dari CA tersebut.

## 6. Hubungkan simulator yang sudah ada

Jangan mengganti seluruh file simulator. Setelah tes di atas berhasil, ubah bagian setup client di simulator yang sudah ada agar memakai username/password simulator serta:

```python
client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)
client.tls_set(ca_certs=MQTT_CA_CERT, tls_version=ssl.PROTOCOL_TLS_CLIENT)
client.tls_insecure_set(False)
client.connect(MQTT_HOST, 8883, 60)
```

Import `ssl` diperlukan. Ambil credential dari environment/`.env` lokal yang masuk `.gitignore`. Pertahankan topic dan callback yang sudah ada. Jangan jalankan smoke test bersamaan jika client ID bertabrakan.

## 7. Belum untuk internet / Vercel

Konfigurasi ini sengaja hanya bind ke `127.0.0.1`; Vercel dan perangkat di jaringan lain tidak akan dapat mencapainya. **Jangan** mengubah bind jadi `0.0.0.0`, membuka port di router, atau menonaktifkan TLS untuk membuatnya terjangkau. Tahap produksi berikutnya adalah broker VPS/managed dengan DNS publik dan sertifikat valid, firewall inbound hanya pada port TLS MQTT, SSH yang dibatasi, akun/ACL per perangkat, dan kredensial disimpan sebagai Vercel environment variables. Setelah broker itu siap, baru atur ulang `MQTT_HOST` dan sertifikat CA secara terkontrol.

## Keamanan command dispense

ACL melarang simulator menerbitkan ke topic command. Pastikan publisher command dari backend menggunakan `retain=False`, command memiliki ID unik dan masa berlaku, perangkat mencegah pemrosesan ulang command yang sama, dan status `accepted/started/completed` dibedakan. Publish MQTT yang sukses bukan bukti produk berhasil keluar. Jangan menguji publish command pada mesin fisik sampai payload, validasi, dan alur ACK/reconciliation diverifikasi.

## Referensi resmi

- Mosquitto authentication/password files: https://mosquitto.org/documentation/authentication-methods/
- Mosquitto configuration: https://mosquitto.org/man/mosquitto-conf-5.html
- Mosquitto TLS: https://mosquitto.org/man/mosquitto-tls-7.html
