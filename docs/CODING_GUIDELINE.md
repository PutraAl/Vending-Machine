# Coding Guideline

## 1. Prinsip umum

- Utamakan solusi sederhana, terbaca, dan sesuai pola repository yang sudah ada.
- Jangan melakukan refactor besar yang tidak berhubungan dengan task.
- Jangan menambah dependency/framework sebelum kebutuhan dan dampaknya jelas.
- Hindari duplikasi aturan bisnis, terutama perhitungan stok dan validasi dispensing.
- Dokumentasikan perubahan kontrak antar komponen.

## 2. Laravel / PHP

- Ikuti versi Laravel 12 dan gaya kode yang dipakai project.
- Gunakan Eloquent relationship bila sesuai dengan model yang sudah ada.
- Letakkan validasi pada Form Request atau tempat terpusat mengikuti pola project.
- Letakkan authorization pada middleware/policy/gate; jangan hanya di Blade.
- Gunakan transaction untuk operasi yang harus konsisten, termasuk pengolahan pembayaran/order/stok.
- Gunakan row lock atau conditional update yang aman bila beberapa request dapat mengubah stok bersamaan.
- Pisahkan tanggung jawab: controller mengelola HTTP flow, service/action untuk alur bisnis yang kompleks, model untuk relasi/casts, dan resource untuk format response bila pola tersebut dipakai.
- Jangan menelan exception secara diam-diam. Catat konteks yang aman dan berikan error response yang konsisten.
- Jangan menyimpan password/token secara plaintext.

## 3. Inventory dan dispensing

- Stok harus dibaca dari sumber database otoritatif.
- Tentukan apakah stok dikelola per slot atau sumber tunggal lain berdasarkan skema aktual.
- Tidak boleh ada angka stok hard-coded sebagai sumber keputusan.
- Jangan membiarkan simulator mengurangi stok database/bisnis secara mandiri.
- Kurangi/reservasi stok setelah pembayaran terverifikasi dengan idempotensi dan transaksi.
- Jangan mengurangi stok lagi saat command MQTT atau event `done` diulang.
- Pisahkan `payment_status`, `order_status`, `machine_status`, dan `dispense_status`.
- Jangan menganggap MQTT publish berhasil berarti command telah dieksekusi.
- Catat `order_id`/`command_id` untuk korelasi dan rekonsiliasi.
- Untuk timeout atau hasil ambigu, tandai perlu pemeriksaan; hindari kompensasi stok otomatis yang berpotensi menambah stok secara keliru.

## 4. Python / MQTT simulator

- Gunakan virtual environment dan dependency yang tercatat pada file requirement project.
- Muat broker host/port/credential dari environment atau konfigurasi lokal yang tidak di-commit.
- Validasi JSON dan semua field wajib; tangani payload invalid tanpa membuat subscriber berhenti mendadak.
- Gunakan reconnect/backoff yang wajar bila sesuai kebutuhan.
- Bedakan command yang diterima, proses yang berjalan, proses selesai, dan proses gagal.
- Sertakan machine ID, command ID, dan order ID pada event terkait bila kontrak mengharuskannya.
- Jangan menetapkan stok permanen di variabel simulator. Sinkronkan status dari backend.
- Dokumentasikan topic dan contoh payload dengan versi/format konsisten.

## 5. API

- Gunakan nama endpoint dan bentuk response yang stabil.
- Validasi request di backend dan gunakan HTTP status yang tepat.
- Dokumentasikan authentication, permission, request, response, error, dan idempotency.
- Jangan mengubah field/path secara diam-diam karena kelompok kiosk memakai API dari laptop/repository berbeda.
- Gunakan Postman Collection dan environment contoh tanpa token/secret nyata.
- Gunakan tunnel atau deployment yang disetujui untuk tes lintas laptop; `localhost` pada satu laptop tidak otomatis dapat diakses laptop lain.

## 6. Frontend dashboard

- Ikuti Blade, Tailwind CSS, dan Vite yang sudah dipakai.
- Livewire tidak digunakan dalam keputusan project saat ini.
- Pastikan UI responsif, khususnya sidebar mobile, overlay, tombol tutup, serta navigasi keyboard dasar.
- Gunakan nilai dari API/database, bukan data dummy untuk alur yang sudah terhubung.
- Tampilkan loading, empty state, error state, dan success state.
- Jangan menyembunyikan kegagalan API dengan data palsu.
- Jangan menampilkan aksi yang tidak boleh dilakukan role pengguna saat ini.

## 7. Environment dan secret

- Jangan commit `.env`, token, password database, credential MQTT, atau key pembayaran.
- Perbarui `.env.example` dengan nama variabel dan contoh non-rahasia bila konfigurasi baru diperlukan.
- Jangan mengubah `.gitignore` hingga file secret/data lokal terlanjur masuk commit.
- Docker dan setup lokal yang sudah ada harus tetap terdokumentasi dan tidak saling merusak.

## 8. Database dan migrations

- Periksa migration dan skema aktual sebelum membuat perubahan.
- Jangan mengubah migration lama yang telah digunakan bersama tanpa alasan kuat; buat migration baru untuk perubahan skema yang diperlukan.
- Jangan menjalankan perintah yang menghapus seluruh data sebagai bagian dari rutinitas coding.
- Tambahkan index/foreign key/constraint ketika relevan.
- Buat perubahan backward-compatible bila ada integrasi lintas kelompok.
- Sediakan seeder development tanpa credential nyata.

## 9. Pengujian minimum untuk perubahan stok/dispensing

- Stok cukup dan order dibayar: stok diproses sekali dan command dibuat.
- Stok kosong: command tidak dikirim.
- Pembayaran belum sukses/invalid: dispensing ditolak.
- Callback/order/command dikirim dua kali: stok tidak berkurang dua kali.
- Dua order bersamaan bersaing atas stok terakhir: stok tidak menjadi negatif.
- Mesin offline atau publish gagal: status tercatat dan dapat direkonsiliasi.
- Event MQTT invalid/duplikat: tidak merusak stok atau status order.
- Hasil dispensing gagal/ambigu: stok tidak ditambah otomatis tanpa konfirmasi aman.

## 10. Definition of Done

Task dianggap selesai jika:

- Implementasi sesuai scope dan pola kode yang ada.
- Input tervalidasi dan akses dikunci sesuai role.
- Test relevan dijalankan; hasil aktual dilaporkan.
- Dokumentasi API/skema/konfigurasi diperbarui bila berubah.
- Tidak ada secret atau file hasil build yang tidak semestinya masuk Git.
- Ringkasan menjelaskan file yang diubah, cara mengetes, dan keterbatasan yang masih ada.
