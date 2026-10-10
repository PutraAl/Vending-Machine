# Role & Permission Specification

## 1. Prinsip

Authorization wajib diterapkan di backend melalui middleware, policy, gate, atau pemeriksaan terpusat sesuai pola Laravel yang ada. Menyembunyikan tombol di dashboard bukan pengamanan yang cukup.

Project membahas tiga role pengguna dashboard: **Admin/Super Admin**, **Teknisi**, dan **Penjual/Operator**. **Pembeli** menggunakan kiosk dan tidak perlu akun dashboard pada scope yang disepakati, kecuali kebutuhan integrasi berikutnya mengubah keputusan tersebut.

## 2. Matriks permission awal

| Fitur | Admin/Super Admin | Teknisi | Penjual/Operator | Pembeli / kiosk |
|---|---|---|---|---|
| Login dashboard | Ya | Ya | Ya | Tidak untuk dashboard admin |
| Lihat ringkasan dashboard | Ya | Ya, fokus mesin | Ya, fokus produk/stok | Tidak |
| Kelola pengguna/role | Ya | Tidak | Tidak | Tidak |
| Lihat daftar mesin dan status | Ya | Ya | Ya, sesuai kebutuhan operasional | Hanya status yang memang perlu ditampilkan di kiosk |
| Monitor suhu dan telemetry | Ya | Ya | Read-only bila diperlukan | Tidak |
| Terima/lihat peringatan teknis | Ya | Ya | Tidak, kecuali diputuskan | Tidak |
| CRUD katalog produk | Ya | Tidak | Ya | Read-only produk aktif via API |
| Kelola penempatan slot dan stok | Ya | Tidak secara default | Ya | Tidak |
| Menjalankan diagnosa/service mesin | Ya atau ditetapkan admin | Ya | Tidak | Tidak |
| Meminta dispensing | Sesuai kebutuhan/otorisasi service | Tidak secara default | Tidak secara default | Melalui alur order/kiosk yang divalidasi backend |
| Lihat log dispensing | Ya | Ya untuk troubleshooting | Ya untuk transaksi operasional sesuai kebutuhan | Hanya status order miliknya jika sistem mendukung identitas order |
| Ubah konfigurasi mesin sensitif | Ya | Terbatas sesuai tugas | Tidak | Tidak |

Matriks ini adalah titik awal. Tegaskan permission aktual per route dan per halaman sebelum implementasi.

## 3. Definisi role

### Admin / Super Admin
- Akses administrasi menyeluruh yang diperlukan project.
- Mengelola akun dan role.
- Melihat status mesin, telemetry, produk, stok, dan log.
- Mengatur konfigurasi yang memang menjadi tanggung jawab aplikasi.
- Tidak boleh melewati aturan transaksi atau konsistensi stok.

### Teknisi
- Memantau suhu, status konektivitas, telemetry, dan error mesin.
- Memeriksa log dispensing untuk troubleshooting.
- Melakukan tindakan teknis yang memang disediakan aplikasi.
- Tidak mengelola akun/role dan tidak mengubah stok bisnis kecuali ada kebutuhan yang disetujui.

### Penjual / Operator
- Mengelola katalog produk sesuai kewenangan.
- Mengelola stok dan penempatan produk pada slot yang tersedia.
- Memantau status mesin dan log operasional yang dibutuhkan.
- Tidak mengelola akun, role, atau konfigurasi teknis sensitif.

### Pembeli / kiosk
- Tidak memerlukan login dashboard.
- Hanya mengakses katalog dan alur order melalui API yang dipublikasikan.
- Tidak boleh menetapkan sendiri status pembayaran, stok, harga final, atau status dispensing.
- Kiosk bukan pengganti autentikasi server-to-server untuk callback pembayaran.

### Service account / perangkat mesin

Jika backend dan simulator berkomunikasi melalui endpoint privat, gunakan token/service identity terpisah dari akun manusia. Beri izin minimum: mengirim telemetry atau menerima/konfirmasi command sesuai peran perangkat. Jangan memakai token admin di simulator.

## 4. Aturan otorisasi

- Terapkan middleware/policy pada route backend.
- Validasi object ownership/scope bila ada banyak mesin atau lokasi.
- Jangan mempercayai `role`, `user_id`, `machine_id`, harga, stok, atau status pembayaran yang dapat dimanipulasi client.
- Endpoint internal harus memakai token/credential yang sesuai.
- Uji akses yang diizinkan dan ditolak untuk tiap role.
- Saat permission berubah, update dokumentasi, test, dan UI terkait bersama-sama.
