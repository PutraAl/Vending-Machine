# System Overview — Sistem Perangkat Lunak Mesin Makanan Panas Otomatis

## 1. Ringkasan

Project ini mengembangkan perangkat lunak pendukung vending machine makanan panas berbasis IoT. Backend Laravel bertanggung jawab atas data katalog, stok, pesanan yang siap diproses, status mesin, hak akses, dan API. Simulator Python/MQTT merepresentasikan perangkat mesin untuk menguji komunikasi, telemetry, dan proses dispensing.

Dokumen ini menjelaskan arsitektur target. Detail yang tidak tercermin di kode aktif harus diverifikasi sebelum implementasi dilakukan.

## 2. Tujuan sistem

- Menyediakan data produk dan stok yang konsisten untuk dashboard dan frontend kiosk.
- Memastikan mesin hanya menerima perintah dispensing yang valid.
- Mengikuti stok yang tercatat di database, bukan stok hard-coded di simulator.
- Memantau status mesin dan suhu secara berkelanjutan.
- Memungkinkan tim lain mengintegrasikan kiosk dan pembayaran melalui kontrak API yang jelas.
- Menyediakan dashboard untuk monitoring operasional dan pengelolaan sesuai role.

## 3. Scope

### Dalam scope

- Backend Laravel 12 dan REST API.
- CRUD katalog produk dan pengaturan slot.
- Alur validasi pesanan/pembayaran eksternal sebelum dispensing.
- Sinkronisasi stok database dengan simulator/perangkat.
- State machine dispensing.
- MQTT telemetry dan event perangkat.
- Dashboard Admin, Teknisi, dan Operator.
- Authentication, authorization, automated tests, serta dokumentasi API.

### Di luar scope utama

- Implementasi payment gateway milik kelompok pembayaran. Backend hanya mengintegrasikan hasil pembayaran lewat mekanisme yang disepakati.
- AI nutrisi/analisis nutrisi milik kelompok lain. Kolom nutrisi tidak perlu ditambahkan tanpa kebutuhan integrasi yang nyata.
- Pembuatan ulang frontend kiosk kelompok lain. Fokusnya menyediakan dan menjaga kontrak API.

## 4. Arsitektur logis

```text
Frontend Kiosk / Tim Pembayaran
            |
            | HTTPS REST API / status pembayaran terverifikasi
            v
      Laravel 12 Backend
      - Authentication & Roles
      - Product Catalog
      - Orders / Payment State Integration
      - Inventory & Slot Management
      - Dispense Orchestration
      - Machine Status API
            |
            +------ Database
            |        - Users / Roles
            |        - Products
            |        - Machines / Slots
            |        - Orders / Order Items
            |        - Dispense Logs / Telemetry
            |
            +------ MQTT Broker (Mosquitto)
                         |
                         v
                Python Simulator / Hardware
                - Telemetry: temperature/status
                - Receive dispense command
                - Report progress/result
```

Diagram ini konseptual; penamaan tabel, class, dan topic MQTT harus mengikuti implementasi yang sudah ada setelah diperiksa.

## 5. Komponen dan tanggung jawab

| Komponen | Tanggung jawab |
|---|---|
| Laravel API | Validasi request, authorization, pengelolaan data, konsistensi stok, dan orkestrasi dispensing. |
| Database | Sumber kebenaran untuk katalog, stok operasional, status order, dan catatan aktivitas. |
| MQTT broker | Mengirimkan command dan menerima telemetry/event; bukan database bisnis. |
| Simulator Python | Mensimulasikan perilaku mesin dan melaporkan status yang terjadi. Tidak menjadi sumber stok utama. |
| Dashboard admin | Mengelola/memantau sesuai role. |
| Frontend kiosk | Menampilkan katalog dan meminta proses sesuai API yang disepakati. |
| Sistem pembayaran eksternal | Memproses pembayaran dan menyediakan konfirmasi yang dapat diverifikasi backend. |

## 6. Alur order dan stok

1. Kiosk mengirim atau menyelesaikan order melalui API yang ditetapkan bersama tim terkait.
2. Backend memverifikasi bahwa order valid dan status pembayaran sudah dikonfirmasi melalui sumber tepercaya.
3. Backend memeriksa stok di database dan mengunci data yang relevan bila diperlukan untuk menghindari race condition.
4. Dalam transaksi database, backend menandai pemrosesan pembayaran/order dan mengurangi atau mereservasi stok **tepat satu kali** sesuai kebijakan inventory yang dipakai.
5. Backend membuat command dispensing dengan ID/korelasi unik, lalu menerbitkan command melalui MQTT atau mekanisme yang sudah ada.
6. Simulator/perangkat menjalankan alur `IDLE → VALIDATING → DISPENSING → DONE → IDLE` atau melaporkan `FAILED` jika proses gagal.
7. Backend menyimpan status/event dan dashboard menampilkan status terbaru.
8. Jika hasil dispensing tidak diketahui atau gagal, sistem melakukan rekonsiliasi. Stok tidak boleh otomatis dikembalikan sebelum dipastikan produk tidak keluar.

**Catatan desain:** pengguna telah menetapkan bahwa stok berkurang setelah pembayaran berhasil. Implementasi harus mencegah pengurangan ganda ketika callback, HTTP request, atau pesan MQTT dikirim ulang. Jika tim memilih model reservasi sebelum dispensing, dokumentasikan kapan reservasi menjadi pengurangan final.

## 7. State machine

State minimum yang disepakati:

- `IDLE`: tidak ada proses dispensing aktif.
- `VALIDATING`: memeriksa command, slot, dan prasyarat.
- `DISPENSING`: proses pengeluaran produk berjalan.
- `DONE`: proses selesai dan hasil dicatat.
- `FAILED`/`ERROR`: proses gagal atau membutuhkan pemeriksaan.

Suhu dipantau secara kontinu sebagai telemetry/condition, bukan melalui state pemanasan pada setiap transaksi. Batas suhu aman harus berasal dari konfigurasi yang disepakati; jangan mengarang angka ambang.

## 8. Prinsip inventory

- Stok database adalah rujukan untuk API, dashboard, dan simulator.
- Tentukan satu sumber stok operasional yang otoritatif. Jika stok per slot mesin, stok slot adalah nilai utama; total stok produk lintas mesin sebaiknya dihitung dari slot, bukan dipelihara sebagai angka duplikat yang dapat berbeda.
- Simulator boleh memiliki cache untuk simulasi, tetapi harus dapat disinkronkan dengan backend dan tidak dapat mengubah stok bisnis secara mandiri.
- UI tidak boleh menganggap data cache sebagai stok terbaru bila endpoint sinkronisasi tersedia.

## 9. Keamanan dan keandalan

- Semua endpoint yang mengubah data harus memiliki validasi dan authorization.
- Konfirmasi pembayaran tidak boleh dipercaya hanya karena dikirim dari browser/kiosk. Gunakan callback server-to-server, verifikasi status ke sistem pembayaran, atau mekanisme tepercaya yang disepakati.
- Lindungi API dan broker MQTT dengan kredensial/ACL sesuai kemampuan lingkungan.
- Gunakan idempotency key/unique business key untuk mencegah duplikasi.
- Catat perubahan stok dan hasil dispensing untuk audit/reconciliation.
- Jangan mengekspos credential, stack trace, atau informasi sensitif dalam response/log publik.

## 10. Hal yang harus diverifikasi di repository

- Sumber stok aktual: tabel/kolom yang sekarang digunakan.
- Nama dan struktur model `Machine`, slot, produk, dan order.
- Route aktual untuk `/dispense` dan `/machine-status` setelah prefix `/api`.
- Format payload MQTT, topic, dan status yang digunakan simulator.
- Mekanisme konfirmasi pembayaran yang sudah disepakati antar kelompok.
- Test yang tersedia dan migrasi yang sudah diterapkan.
