# Codex / AI Code Inspection Notes

## 1. Tujuan

Dokumen ini menjadi catatan inspeksi sebelum coding agent melakukan perubahan. Ini bukan pengganti pemeriksaan repository langsung. Status project dapat berubah; setiap agent harus memverifikasi ulang kondisi aktual.

## 2. Konteks yang diketahui dari sesi pengerjaan

| Area | Informasi terakhir yang dibahas | Tindakan saat mulai sesi baru |
|---|---|---|
| Framework | Laravel 12 | Cek `composer.json` dan versi runtime. |
| Authentication API | Laravel Sanctum v4.3.3 pernah dipasang | Cek dependency aktual, `User` model, migration token, config, dan middleware. |
| API routes | `routes/api.php` pernah didaftarkan melalui `bootstrap/app.php` | Jalankan `php artisan route:list --path=api`. |
| Tests | Pengguna pernah melaporkan test lulus setelah perubahan Sanctum | Jalankan ulang test yang relevan; jangan menganggap status masih sama. |
| Machine model | `app/Models/Machine.php` harus dipertahankan dan diperiksa sebelum diedit | Baca model, relasi, casts, dan pemakaian di project. |
| Simulator | Python + Paho MQTT + Mosquitto pernah digunakan | Periksa folder aktual, environment, topic, dan format payload. |
| Frontend admin | Laravel/Tailwind/Vite; Livewire tidak dipilih | Jangan memasukkan Livewire. Periksa konfigurasi Vite dan pola UI aktual. |
| Pembayaran & nutrisi AI | Milik kelompok lain | Jangan mengimplementasikan sistem pembayaran atau AI nutrisi sendiri. Integrasikan hanya lewat kontrak. |

## 3. Pemeriksaan awal wajib

Sebelum mengedit, lakukan inspeksi yang sesuai dengan environment:

```bash
git status --short
php artisan --version
php artisan route:list --path=api
php artisan migrate:status
php artisan test
```

Periksa juga:

- `composer.json` dan `composer.lock`
- `bootstrap/app.php`
- `routes/api.php` dan `routes/web.php`
- `app/Models/User.php`
- `app/Models/Machine.php`
- Model terkait produk, slot, order, telemetry, dan log dispensing
- Migration serta seeder yang relevan
- Middleware/policy/Form Request/controller/service yang sudah ada
- `config/sanctum.php` jika tersedia
- folder simulator dan berkas konfigurasi contoh seperti `.env.example`
- dokumen API, role permission, dan database yang sudah tersimpan

Jangan mengasumsikan nama file atau class dari dokumen ini pasti sama persis dengan repository.

## 4. Temuan yang perlu diverifikasi

- [ ] Sanctum masih terpasang dan token/API auth berjalan sesuai desain.
- [ ] API routes masuk ke route stack yang benar.
- [ ] Tidak ada duplikasi route akibat prefix `api` yang ditambahkan dua kali.
- [ ] Role enforcement sudah diterapkan di backend, bukan hanya menyembunyikan menu UI.
- [ ] Endpoint katalog menampilkan produk aktif dan informasi yang memang dibutuhkan kiosk.
- [ ] Sumber stok tunggal telah dipilih dan seluruh alur memakai sumber itu.
- [ ] Order hanya dapat memulai dispensing setelah status pembayaran terverifikasi.
- [ ] Proses pengurangan stok idempotent dan terlindungi dari request/event duplikat.
- [ ] `/dispense` dan `/machine-status` benar-benar terdaftar pada path aktual yang terdokumentasi.
- [ ] Format payload MQTT konsisten antara Laravel dan simulator.
- [ ] Dashboard responsif di desktop dan mobile.
- [ ] Test untuk stok habis, mesin offline, dan dispensing gagal tersedia atau direncanakan.

## 5. Guardrails untuk agent

- Baca implementasi aktual sebelum menyimpulkan bug atau menulis ulang fitur.
- Pertahankan perubahan lokal pengguna; jangan reset atau overwrite secara massal.
- Jangan ubah `Machine.php` atau dokumen ini secara menyeluruh tanpa kebutuhan dan peninjauan isi.
- Jangan membuat migration destruktif atau menjalankan `migrate:fresh` tanpa persetujuan eksplisit.
- Jangan laporkan inspeksi/test sebagai selesai sebelum benar-benar dilakukan.
- Catat file yang diubah, asumsi, test yang dijalankan, dan hal yang belum diverifikasi pada akhir task.

## 6. Format catatan inspeksi per sesi

Tambahkan entri singkat bila sesi menghasilkan temuan penting:

```text
Tanggal:
Task:
File yang diperiksa:
Temuan terverifikasi:
Asumsi yang belum terverifikasi:
File yang diubah:
Perintah/test dan hasil:
Risiko / tindak lanjut:
```
