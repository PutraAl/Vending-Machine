# Database Schema — Acuan Data Project

## 1. Tujuan dokumen

Dokumen ini mendefinisikan model data logis yang diperlukan untuk menghubungkan katalog, stok slot, order, status mesin, dan proses dispensing. Ini **bukan klaim bahwa semua tabel/kolom berikut sudah ada**. Sebelum menulis migration, periksa migration aktif, model Eloquent, relasi, seeder, dan data yang ada.

**Aturan:** jangan membuat migration duplikat atau mengubah nama kolom hanya agar cocok dengan dokumen ini. Bila skema aktual berbeda, perbarui dokumen ini atau buat rencana migrasi yang eksplisit.

## 2. Prinsip desain

1. Database adalah sumber kebenaran bisnis untuk stok dan status order.
2. Pilih satu sumber stok operasional yang otoritatif. Untuk mesin dengan slot fisik, pilihan yang disarankan adalah stok pada `machine_slots`.
3. `products` menyimpan informasi produk; `machine_slots` menyimpan penempatan produk dan kuantitas fisik pada mesin tertentu.
4. Jika project sekarang sudah memakai `products.stock`, jangan langsung menghapusnya. Audit penggunaannya terlebih dahulu dan tentukan apakah kolom itu stok total, stok per mesin, atau hanya data lama.
5. Setiap order atau perintah dispensing harus memiliki identitas untuk mencegah diproses dua kali.
6. Perubahan stok dan status order terkait harus konsisten/transaksional.

## 3. Entitas logis yang disarankan

### `users`
Untuk pengguna dashboard dan/atau akun internal.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key. |
| `name` | Nama pengguna. |
| `email` | Identitas login yang unik bila login menggunakan email. |
| `password` | Password hash; jangan simpan plaintext. |
| `role` atau relasi role | Admin, Teknisi, atau Operator sesuai pola authorization project. |
| `created_at`, `updated_at` | Timestamp Laravel. |

Gunakan struktur role yang sudah ada bila tersedia. Jangan memperkenalkan package role-permission baru tanpa kebutuhan.

### `products`
Menyimpan katalog produk.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key. |
| `name` | Nama produk. |
| `sku`/`code` | Kode unik bila diperlukan. |
| `description` | Deskripsi opsional. |
| `price` | Harga; gunakan tipe presisi yang sesuai dan konsisten. |
| `image` | Path/URL gambar sesuai pola aplikasi. |
| `is_active` atau `status` | Apakah produk tersedia untuk ditampilkan/dipesan. |
| timestamps | Timestamp Laravel. |

Fitur nutrisi/AI bukan scope utama. Jangan menambah kolom nutrisi kecuali kontrak integrasi dari kelompok lain sudah jelas.

### `machines`
Mewakili unit vending machine.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key. |
| `machine_code` | Identitas mesin yang unik, bila diperlukan. |
| `name` | Nama/label mesin. |
| `location` | Lokasi opsional. |
| `status` | Status operasional; gunakan enum/string yang konsisten. |
| `last_seen_at` | Waktu terakhir telemetry diterima, jika dibutuhkan. |
| timestamps | Timestamp Laravel. |

Jika model `Machine.php` sudah ada, inspeksi dan pertahankan perilakunya. Jangan mengganti model tersebut secara menyeluruh tanpa alasan.

### `machine_slots` (atau tabel slot dengan nama yang sudah ada)
Mewakili slot fisik di mesin. Ini menjadi **sumber stok operasional yang disarankan** bila kuantitas perlu dilacak per slot.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key. |
| `machine_id` | Foreign key ke mesin. |
| `product_id` | Produk yang saat ini ditempatkan pada slot; dapat nullable bila slot kosong. |
| `slot_code` | Identitas slot pada mesin, unik dalam cakupan mesin. |
| `stock` | Kuantitas yang tercatat untuk slot ini. |
| `capacity` | Kapasitas maksimum opsional. |
| `is_active` atau `status` | Apakah slot siap digunakan. |
| timestamps | Timestamp Laravel. |

Constraint yang patut dipertimbangkan: kombinasi `(machine_id, slot_code)` unik; `stock >= 0`; `capacity >= stock` jika kapasitas digunakan. Implementasikan sesuai dukungan database dan pola migration project.

### `orders`
Mewakili order dari kiosk/layanan eksternal.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key internal. |
| `order_code` atau `external_order_id` | ID yang stabil untuk korelasi dan idempotensi. |
| `payment_status` | Status pembayaran yang sudah diverifikasi. |
| `status` | Status fulfillment, misalnya pending, ready, dispensing, completed, failed. Pisahkan dari status pembayaran. |
| `total_amount` | Nilai transaksi bila order dikelola backend ini. |
| `paid_at` | Waktu pembayaran terkonfirmasi. |
| `stock_processed_at` atau penanda setara | Penanda bahwa stok untuk order telah diproses, jika dipakai untuk idempotensi. |
| timestamps | Timestamp Laravel. |

Gunakan penamaan status dan field yang konsisten dengan struktur nyata. Jangan mengubah status pembayaran dan status dispensing menjadi satu status ambigu.

### `order_items`
Menyimpan produk/slot dan jumlah untuk setiap order.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key. |
| `order_id` | Foreign key ke order. |
| `product_id` | Produk yang dipesan. |
| `machine_slot_id` atau `slot_id` | Slot yang akan mengeluarkan produk, jika pemilihan slot dilakukan di backend. |
| `quantity` | Jumlah unit. |
| `unit_price` | Harga saat order dibuat bila dibutuhkan. |
| timestamps | Timestamp Laravel. |

Simpan snapshot harga hanya jika backend memang bertanggung jawab atas total/riwayat harga. Hindari duplikasi data yang tidak diperlukan.

### `dispense_logs` atau `dispense_commands`
Merekam proses dan hasil dispensing.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key. |
| `order_id` | Order yang memicu dispensing, bila ada. |
| `machine_id` | Mesin sasaran. |
| `slot_id` | Slot sasaran. |
| `command_id` | ID unik command untuk idempotensi/korelasi. |
| `status` | queued, sent, dispensing, done, failed, atau status setara yang disepakati. |
| `requested_at`, `started_at`, `finished_at` | Waktu lifecycle bila diperlukan. |
| `result`/`error_code` | Hasil ringkas yang aman untuk audit. |
| timestamps | Timestamp Laravel. |

Gunakan struktur yang ada bila audit log telah disediakan di tabel lain. Hindari menyimpan payload sensitif secara utuh tanpa kebutuhan.

### `machine_telemetries` (opsional)
Menyimpan telemetry yang perlu diakses ulang, bukan hanya data terakhir.

| Field logis | Keterangan |
|---|---|
| `id` | Primary key. |
| `machine_id` | Mesin pengirim. |
| `temperature` | Suhu yang dilaporkan. |
| `machine_status` | Status mesin saat telemetry dikirim. |
| `payload` | JSON terbatas bila dibutuhkan untuk diagnostik. |
| `recorded_at` | Timestamp dari event dengan validasi yang wajar. |
| timestamps | Timestamp Laravel. |

Jika hanya perlu menampilkan status terakhir, menyimpan `last_temperature`, `status`, dan `last_seen_at` pada mesin bisa lebih sederhana. Pilih satu rancangan berdasarkan kebutuhan, bukan membuat keduanya tanpa alasan.

## 4. Relasi utama

```text
machines 1 ─── * machine_slots * ─── 1 products
orders   1 ─── * order_items * ─── 1 products
order_items * ─── 0..1 machine_slots
orders   1 ─── * dispense_logs
machines 1 ─── * dispense_logs
machines 1 ─── * machine_telemetries (opsional)
```

Relasi aktual bisa berbeda; gunakan foreign key dan relasi Eloquent yang konsisten.

## 5. Alur stok yang aman

- Verifikasi bahwa order dibayar melalui mekanisme tepercaya.
- Dalam transaction, baca/lock record stok yang relevan dan pastikan kuantitas mencukupi.
- Terapkan pengurangan/reservasi sekali saja berdasarkan ID order atau idempotency key.
- Tandai perubahan stok/order dalam transaction yang sama jika skemanya mendukung.
- Setelah commit, terbitkan perintah ke MQTT/queue. Jangan menahan transaction database tetap terbuka selama menunggu hardware.
- Catat command ID dan update status dari event simulator/perangkat.
- Untuk command duplikat, gunakan ID yang sama dan jangan mengurangi stok lagi.
- Untuk kegagalan/timeout, tandai status untuk rekonsiliasi. Jangan mengembalikan stok secara otomatis tanpa aturan bisnis yang jelas dan konfirmasi bahwa produk tidak keluar.

## 6. Sebelum membuat migration

Checklist:

- [ ] Baca semua migration terkait produk, mesin, slot, order, dan users.
- [ ] Cek model serta relasi Eloquent.
- [ ] Cek controller/service yang membaca atau mengubah stok.
- [ ] Cari semua referensi `stock`, `slot`, `order`, dan `machine`.
- [ ] Pastikan data lama tidak hilang dan migration bisa di-rollback bila sesuai.
- [ ] Tambahkan/ubah test untuk stok kosong, stok bersamaan, order duplikat, dan pembayaran belum berhasil.
- [ ] Update dokumen setelah skema final diverifikasi.
