# MQTT Contract — Sistem Perangkat Lunak Mesin Makanan Panas Otomatis

## 1. Tujuan dan status dokumen

Dokumen ini mendefinisikan kontrak komunikasi MQTT antara backend Laravel, broker Mosquitto, dan simulator Python/perangkat vending machine.

**Status: rancangan kontrak awal / wajib diverifikasi terhadap kode aktual.** Nama topic, format payload, versi MQTT, QoS, dan cara publish/subscribe yang sudah ada di repository harus diperiksa terlebih dahulu. Jangan mengganti implementasi aktif secara sepihak hanya agar cocok dengan dokumen ini. Jika ada perbedaan, dokumentasikan perbedaannya dan sepakati perubahan dengan anggota tim terkait.

Dokumen ini melengkapi `docs/API_SPECIFICATION.md`:

- REST API mengatur komunikasi HTTP dengan kiosk, dashboard, dan integrasi eksternal.
- MQTT Contract mengatur pesan asynchronous antara backend dan mesin/simulator.
- Keduanya bukan pengganti satu sama lain.

## 2. Prinsip utama

1. **Database/backend adalah sumber kebenaran stok bisnis.** Simulator tidak boleh mengurangi atau mengubah stok database secara mandiri.
2. **MQTT adalah kanal komunikasi, bukan database bisnis.** Pesan telemetry, command, dan event bukan pengganti catatan transaksi database.
3. **Backend memutuskan apakah dispensing boleh dilakukan.** Simulator tidak boleh menganggap order valid hanya berdasarkan pesan MQTT yang tidak terautentikasi/terotorisasi.
4. **Setiap command memiliki identitas stabil.** Gunakan `command_id` untuk deduplikasi dan korelasi retry/event.
5. **Pesan dapat terkirim lebih dari satu kali.** Consumer wajib idempotent dan tidak boleh menjalankan satu command atau memproses stok dua kali.
6. **Perintah terkirim bukan bukti dispensing berhasil.** Hasil perlu dilaporkan melalui event dan dicatat oleh backend.
7. **Jangan mengirim secret atau data pembayaran sensitif lewat MQTT.** Tidak perlu menyertakan token pembayaran, nomor kartu, atau bukti pembayaran mentah dalam pesan mesin.
8. **Suhu dimonitor terus-menerus.** Jangan menambahkan fase pemanasan ulang pada setiap transaksi; aturan ambang suhu harus berasal dari konfigurasi yang disetujui.

## 3. Arsitektur dan arah pesan

```text
Python Simulator / Device
    |-- telemetry ----------------------> MQTT Broker --> Laravel MQTT consumer
    |<-- dispense command --------------- Laravel backend
    |-- dispense event / result --------> MQTT Broker --> Laravel MQTT consumer

Laravel REST API <--> Database
                       |
                       +-- authoritative order, slot, and stock records
```

Diagram ini konseptual. Pastikan aplikasi Laravel benar-benar memiliki publisher dan consumer MQTT sebelum menyatakan integrasi end-to-end sudah aktif.

## 4. Topic yang diusulkan

Topik berikut adalah **nama konseptual**, bukan klaim bahwa topic ini sudah digunakan kode sekarang.

| Topic | Publisher | Subscriber | Tujuan | QoS awal | Retained |
|---|---|---|---|---:|---|
| `vending/{machine_id}/telemetry` | Simulator/perangkat | Backend, dashboard gateway bila ada | Telemetri suhu dan kondisi mesin terkini | 0 atau 1 sesuai kebutuhan reliabilitas | Boleh `true` hanya untuk snapshot terkini; timestamp wajib diperiksa |
| `vending/{machine_id}/command/dispense` | Backend | Simulator/perangkat untuk mesin terkait | Meminta dispensing berdasarkan command yang sudah divalidasi backend | 1 | **`false` — command tidak boleh menjadi retained message** |
| `vending/{machine_id}/event/dispense` | Simulator/perangkat | Backend | ACK, progres, serta hasil dispensing | 1 | `false` |
| `vending/{machine_id}/availability` *(opsional)* | Simulator/perangkat atau Last Will broker | Backend/dashboard gateway | Menandai konektivitas mesin `online`/`offline` | 1 | `true` dapat digunakan dengan Last Will yang benar |

Aturan topic:

- `{machine_id}` harus ID mesin yang terdaftar di backend; jangan menerima ID bebas yang tidak dikenal.
- Gunakan topic tanpa slash di awal dan pertahankan huruf besar/kecil secara konsisten.
- Topic produksi dan development sebaiknya dipisahkan oleh prefix/environment bila satu broker dipakai bersama.
- Jangan memublikasikan command ke wildcard topic yang dapat membuat beberapa mesin mengeksekusi order yang sama.
- Jangan mengubah topic aktif sebelum memeriksa konfigurasi Python, consumer/publisher Laravel, `.env.example`, dan dokumentasi tim.

## 5. Format pesan umum

Pesan harus berupa JSON UTF-8 yang valid. Rekomendasi envelope umum:

```json
{
  "schema_version": "1.0",
  "message_id": "<unique-message-uuid>",
  "message_type": "machine.telemetry",
  "machine_id": "VM-01",
  "occurred_at": "2026-10-11T03:30:00Z",
  "data": {}
}
```

Field umum:

| Field | Wajib | Arti |
|---|---|---|
| `schema_version` | Ya | Versi skema payload, tidak sama dengan versi aplikasi. |
| `message_id` | Ya | ID unik untuk satu pesan MQTT; pesan baru memiliki ID baru. |
| `message_type` | Ya | Jenis pesan, misalnya `machine.telemetry`, `dispense.command`, atau `dispense.event`. |
| `machine_id` | Ya | ID mesin yang terdaftar dan sesuai topic. |
| `occurred_at` | Ya | Timestamp ISO 8601 dalam UTC; akhiri dengan `Z`. |
| `data` | Ya | Payload khusus sesuai `message_type`. |

Validasi bahwa `machine_id` pada payload cocok dengan ID pada topic. Tolak JSON invalid, field wajib hilang, tipe data tidak sesuai, timestamp tidak valid, atau pesan dengan mesin yang tidak dikenal. Pesan invalid dicatat secara aman tanpa menyimpan credential atau data sensitif.

## 6. Telemetry mesin

Contoh payload konseptual untuk topic `vending/VM-01/telemetry`:

```json
{
  "schema_version": "1.0",
  "message_id": "<unique-message-uuid>",
  "message_type": "machine.telemetry",
  "machine_id": "VM-01",
  "occurred_at": "2026-10-11T03:30:00Z",
  "data": {
    "machine_state": "IDLE",
    "temperature_c": 68.5
  }
}
```

Ketentuan:

- `temperature_c` menggunakan satuan Celsius dan angka harus finite; ambang peringatan/batas aman dibaca dari konfigurasi yang disetujui.
- `machine_state` gunakan state yang disepakati: `IDLE`, `VALIDATING`, `DISPENSING`, `DONE`, atau `FAILED`. Jika implementasi sekarang memakai nama lain, verifikasi dan sepakati pemetaan terlebih dahulu.
- Field tambahan (misalnya sensor, door status, atau slot sensor) hanya ditambahkan bila benar-benar tersedia dan didefinisikan dengan tipe data jelas.
- Telemetry yang terlambat tidak boleh menimpa kondisi terbaru. Bandingkan timestamp/urutan pesan dengan kebijakan yang konsisten.
- **Jangan menjadikan field stok yang berasal dari simulator sebagai sumber kebenaran stok database.** Bila sensor fisik benar-benar menghitung isi slot, simpan sebagai observasi terpisah dan rancang rekonsiliasi eksplisit.

## 7. Command dispensing: backend → mesin

Contoh konseptual topic `vending/VM-01/command/dispense`:

```json
{
  "schema_version": "1.0",
  "message_id": "<unique-message-uuid>",
  "message_type": "dispense.command",
  "machine_id": "VM-01",
  "occurred_at": "2026-10-11T03:31:00Z",
  "data": {
    "command_id": "CMD-<unique-id>",
    "order_id": "ORD-<stable-order-id>",
    "slot_code": "A1",
    "quantity": 1,
    "expires_at": "2026-10-11T03:32:00Z"
  }
}
```

Field command di atas adalah baseline konseptual. Cocokkan nama dan struktur ID/slot dengan skema aktif. Jangan menambahkan field yang tidak diperlukan.

Sebelum publish command, backend harus:

1. Memvalidasi order dan memastikan status pembayaran sudah dikonfirmasi melalui mekanisme tepercaya yang disepakati.
2. Memastikan order belum berhasil diproses atau memiliki command aktif yang setara.
3. Memvalidasi hubungan order-item, mesin, slot, dan ketersediaan stok.
4. Mencatat perubahan stok atau reservasi tepat satu kali secara transaksional sesuai kebijakan inventory project.
5. Menghasilkan `command_id` stabil untuk percobaan dispensing tersebut dan menyimpan status command/outbox bila pola itu digunakan.
6. Mengirim hanya instruksi minimum yang diperlukan mesin. Mesin tidak boleh menerima atau mempercayai harga maupun klaim status pembayaran dari client.

Perilaku simulator:

- Memvalidasi `message_type`, `machine_id`, field wajib, jumlah, expiry, dan `command_id`.
- Memproses satu `command_id` paling banyak satu kali; retry dari pesan MQTT tidak boleh menyebabkan dispensing berulang.
- Menolak command yang kedaluwarsa, tidak ditujukan untuk mesin ini, invalid, atau tidak sesuai state mesin.
- Jika command yang sama diterima ulang, balas dengan status yang sudah diketahui atau ACK duplikat; jangan menjalankan dispensing lagi.
- MQTT QoS 1 memungkinkan redelivery, sehingga deduplikasi tetap wajib meskipun broker memakai QoS 1.
- Command tidak boleh retained. Expiry/persistence bergantung pada versi dan konfigurasi MQTT yang benar-benar digunakan; `expires_at` tetap perlu divalidasi aplikasi.

## 8. Event dispensing: mesin → backend

Contoh konseptual event pada topic `vending/VM-01/event/dispense`:

```json
{
  "schema_version": "1.0",
  "message_id": "<unique-event-uuid>",
  "message_type": "dispense.event",
  "machine_id": "VM-01",
  "occurred_at": "2026-10-11T03:31:10Z",
  "data": {
    "command_id": "CMD-<unique-id>",
    "order_id": "ORD-<stable-order-id>",
    "slot_code": "A1",
    "status": "done",
    "error_code": null
  }
}
```

Status event yang direkomendasikan:

- `accepted`: command diterima dan valid, belum tentu proses fisik dimulai.
- `started`: dispensing benar-benar mulai dijalankan.
- `done`: simulator/perangkat melaporkan proses selesai.
- `failed`: proses gagal; sertakan `error_code` yang terdefinisi jika tersedia.

Perbedaan status command dan state mesin harus jelas. `DONE` adalah state mesin; `done` adalah hasil command.

Backend wajib:

- Memeriksa mesin, `command_id`, dan `order_id` terhadap record yang dikenal.
- Memastikan event berhubungan dengan command yang benar dan transisi status diperbolehkan.
- Menangani event duplikat secara idempotent, misalnya dengan `message_id` unik dan constraint bisnis pada command.
- Menyimpan event/status dan waktu yang diterima agar bisa diaudit.
- Tidak mengurangi stok sekali lagi saat event `done` diterima jika stok sudah dikurangi/dipesan pada tahap setelah pembayaran berhasil.
- Jika status `failed`, timeout, atau hasilnya tidak pasti, tandai untuk rekonsiliasi sesuai kebijakan bisnis. Jangan otomatis menambah stok kembali sebelum diketahui apakah produk benar-benar keluar.

## 9. State machine dan timeout

Alur logis minimum:

`IDLE → VALIDATING → DISPENSING → DONE → IDLE`

State `FAILED`/`ERROR` dipakai saat kegagalan sesuai implementasi. Pantau suhu secara berkelanjutan; jangan melakukan pemanasan ulang untuk setiap order.

- Backend menyimpan status pemrosesan command/order yang otoritatif untuk kebutuhan bisnis.
- Simulator melaporkan state/progress perangkat aktual, bukan memutuskan status pembayaran atau mengubah stok bisnis.
- Timeout menandakan hasil belum diketahui, bukan otomatis berarti produk gagal keluar.
- Setelah timeout, periksa event terlambat dan kondisi perangkat sebelum retry; jangan mengirim command baru yang berpotensi mendispense order yang sama dua kali.
- `expires_at` harus diperiksa oleh aplikasi. Jangan bergantung pada retained message sebagai mekanisme command queue.

## 10. QoS, retained message, reconnect

Pengaturan QoS/retained pada tabel topic adalah rekomendasi awal dan perlu diuji terhadap broker serta library aktual.

- QoS 0 sesuai untuk telemetry frekuensi tinggi yang kehilangan satu sampel masih bisa ditoleransi.
- QoS 1 direkomendasikan untuk command dan event penting dengan deduplikasi aplikasi karena pesan dapat dikirim ulang.
- Jangan mengasumsikan QoS 1 berarti tepat satu kali eksekusi.
- Command dispensing selalu retained `false`.
- Snapshot telemetry/availability boleh retained jika diperlukan, tetapi gunakan timestamp/last-seen untuk menandai data basi.
- Saat reconnect, simulator mengirim telemetry terbaru dan availability online sesuai rancangan. Backend jangan menafsirkan status lama yang retained sebagai bukti bahwa mesin masih online sekarang.
- Atur Last Will/availability bila didukung dan cocok dengan konfigurasi broker saat ini.

## 11. Keamanan broker dan validasi

- Gunakan autentikasi broker dan ACL per peran/perangkat bila tersedia.
- Mesin hanya boleh subscribe ke command untuk ID mesin itu sendiri dan publish ke topic telemetry/event miliknya.
- Backend hanya boleh publish command ke mesin terotorisasi serta subscribe pada telemetry/event yang diperlukan.
- Jangan memakai credential default pada deployment bersama; rahasiakan credential lewat environment/configuration, bukan repository.
- Validasi ukuran pesan, JSON, topic, ID, enum status, nilai suhu, timestamp, dan batas `quantity`.
- Jangan memasukkan password, token, isi `.env`, atau detail pembayaran sensitif ke log payload.
- Pisahkan akses broker development dan deployment jika dibutuhkan.

## 12. Error handling dan observability

Minimal catat:

- `machine_id`, `message_id`, `message_type`, `command_id` (jika ada), waktu diterima, hasil validasi, dan alasan penolakan yang aman.
- Kegagalan koneksi/reconnect broker, pesan invalid, command timeout, event duplikat, dan perubahan status command.
- Jangan log credential atau data sensitif.

Kode error yang disarankan untuk command/event gagal (gunakan hanya yang relevan dan petakan ke implementasi nyata): `INVALID_COMMAND`, `UNKNOWN_MACHINE`, `UNKNOWN_SLOT`, `INVALID_STATE`, `COMMAND_EXPIRED`, `SLOT_EMPTY`, `DISPENSE_FAILED`, `DEVICE_ERROR`, `TIMEOUT`. Jangan membuat asumsi penyebab fisik jika simulator/hardware tidak mampu mendeteksinya.

## 13. Checklist pengujian integrasi

- [ ] Topic dan payload di dokumen sudah dibandingkan dengan konfigurasi simulator serta consumer/publisher aktual.
- [ ] Publish/subscribe berhasil menggunakan broker development.
- [ ] Payload JSON invalid, field wajib hilang, tipe salah, dan machine ID tidak sesuai ditolak.
- [ ] Suhu valid diproses dan nilai non-finite/invalid ditolak.
- [ ] Mesin offline/reconnect dan telemetry basi ditangani.
- [ ] Command hanya diterbitkan setelah validasi backend dan konfirmasi pembayaran tepercaya.
- [ ] Command retained dinonaktifkan.
- [ ] Pesan command dengan `command_id` sama tidak menjalankan dispensing dua kali.
- [ ] Event `accepted`, `started`, `done`, dan `failed` diproses sesuai transisi yang diizinkan.
- [ ] Event duplikat tidak menggandakan perubahan order/stok.
- [ ] Timeout tidak langsung menyebabkan command berulang atau stok dikembalikan tanpa rekonsiliasi.
- [ ] Stok database tetap otoritatif; simulator tidak dapat mengubah stok bisnis secara mandiri.
- [ ] Credential broker tidak ditambahkan ke Git atau Postman collection yang dibagikan.

## 14. Sebelum mengubah implementasi

Inspeksi terlebih dahulu:

1. File simulator Python, konfigurasi Paho MQTT, dan semua topic yang sedang dipakai.
2. Publisher/consumer Laravel atau worker yang memproses MQTT (jika sudah ada).
3. Konfigurasi broker Mosquitto dan ACL bila tersedia.
4. Field machine/slot/order/command pada migration dan model aktual.
5. Test serta log integrasi yang sudah ada.

Setelah inspeksi, perbarui contoh dan tabel dokumen ini agar sesuai kontrak final yang digunakan seluruh kelompok. **Dokumen ini tidak membuktikan bahwa topic atau skema contoh sudah diimplementasikan.**
