# API Specification — Vending Machine

## 1. Status dokumen

Dokumen ini adalah kontrak integrasi awal untuk backend Laravel, frontend kiosk, simulator, dan kelompok pembayaran. Nama path/field/status di bawah harus dibandingkan dengan route dan controller yang benar-benar tersedia. Jangan langsung mengubah API produksi hanya agar sesuai dokumen ini; sepakati perubahan dengan semua kelompok.

## 2. Prinsip API

- Prefix API Laravel lazimnya `/api`; path aktual harus dicek dengan `php artisan route:list --path=api`.
- Format response JSON konsisten.
- Endpoint yang mengubah data harus melakukan validasi dan authorization di server.
- API katalog kiosk hanya mengekspos data produk yang diperlukan.
- Status pembayaran harus diverifikasi dari sumber tepercaya; field `payment_status: paid` yang dikirim oleh browser/kiosk saja tidak cukup.
- Request/event duplikat harus aman (idempotent), terutama callback pembayaran dan perintah dispensing.
- Jangan bocorkan stack trace, credential, atau data internal.

## 3. Endpoint fungsional

Nama di tabel ini menggambarkan interface yang disepakati secara konseptual. Konfirmasi path aktual sebelum dipakai tim lain.

| Method | Path konseptual | Kegunaan | Akses |
|---|---|---|---|
| `GET` | `/api/products` | Daftar produk aktif untuk kiosk. | Public read terbatas atau token kiosk sesuai kebijakan deployment. |
| `GET` | `/api/products/{id}` | Detail satu produk. | Public read terbatas atau token kiosk. |
| `GET` | `/api/machine-status` | Status mesin/telemetry terbaru. | Token internal/role yang berwenang; batasi detail yang diekspos. |
| `POST` | `/api/dispense` | Meminta backend memproses dispensing yang valid. | Token service/perangkat atau role yang berwenang; bukan endpoint tanpa proteksi. |
| `GET` | `/api/machines/{machine}/status` | Alternatif status per mesin jika API mendukung banyak mesin. | Sesuai role/token. |

Jika endpoint `/dispense` dan `/machine-status` sudah ada dengan path atau payload berbeda, dokumentasikan bentuk aktual dan pertahankan kompatibilitas sebelum mengubahnya.

## 4. Contoh request katalog

```http
GET /api/products
Accept: application/json
```

Contoh bentuk response ilustratif (samakan dengan response aktual/API Resource):

```json
{
  "data": [
    {
      "id": 12,
      "name": "Contoh Produk",
      "price": 15000,
      "image": null,
      "is_active": true
    }
  ]
}
```

Jangan menggunakan contoh ini sebagai bukti bahwa field tersebut sudah tersedia. Field stok yang ditampilkan harus berasal dari sumber stok aktual dan boleh perlu endpoint terpisah jika stok bergantung pada mesin/slot.

## 5. Contoh request status mesin

```http
GET /api/machine-status
Accept: application/json
Authorization: Bearer <service-token>
```

Contoh payload ilustratif:

```json
{
  "data": {
    "machine_id": "VM-01",
    "status": "IDLE",
    "temperature": 68.5,
    "last_seen_at": "2026-10-10T20:30:00Z"
  }
}
```

Nilai suhu di atas hanya contoh payload, bukan ambang atau target suhu yang disepakati. Gunakan unit suhu, timezone, dan nama field secara konsisten di backend serta simulator.

## 6. Contoh request dispensing

**Prinsip keamanan:** endpoint ini tidak boleh menjadi cara untuk melewati pembayaran atau mengurangi stok tanpa order yang valid. Rekomendasi payload adalah referensi order/command, bukan mempercayai harga atau status pembayaran dari client.

```http
POST /api/dispense
Content-Type: application/json
Accept: application/json
Authorization: Bearer <authorized-token>
Idempotency-Key: <unique-key>
```

Contoh body konseptual:

```json
{
  "order_id": "ORD-12345",
  "machine_id": "VM-01"
}
```

Backend perlu memastikan:

1. Order ditemukan dan pembayaran telah dikonfirmasi melalui mekanisme tepercaya.
2. Order belum diselesaikan/diproses sebelumnya.
3. Slot sesuai dengan item order dan stok tersedia.
4. Pengurangan/reservasi stok dilakukan tepat satu kali secara transaksional.
5. Command memiliki ID unik untuk korelasi event MQTT.
6. Response menyatakan apakah request diterima untuk diproses, bukan mengklaim produk sudah keluar jika hasil hardware belum diterima.

Contoh response penerimaan command:

```json
{
  "data": {
    "command_id": "CMD-EXAMPLE-001",
    "order_id": "ORD-12345",
    "status": "queued"
  }
}
```

Response tersebut ilustratif dan harus disejajarkan dengan bentuk aktual.

## 7. Status HTTP yang disarankan

| HTTP status | Pemakaian umum |
|---|---|
| `200 OK` | Request berhasil dibaca/diproses. |
| `201 Created` | Resource baru dibuat. |
| `202 Accepted` | Command diterima untuk diproses asynchronous. |
| `400 Bad Request` | Request tidak dapat diproses karena format dasar invalid. |
| `401 Unauthorized` | Belum terautentikasi. |
| `403 Forbidden` | Pengguna/token tidak berhak. |
| `404 Not Found` | Resource tidak ditemukan. |
| `409 Conflict` | Stok tidak cukup, state tidak cocok, atau request duplikat berkonflik. |
| `422 Unprocessable Entity` | Validasi field gagal. |
| `429 Too Many Requests` | Batas request terlampaui. |
| `500/503` | Kesalahan server atau dependency tidak tersedia; detail internal tidak ditampilkan ke client. |

Jangan memaksakan semua status ini jika konvensi project sudah menetapkan bentuk lain; jaga konsistensi.

## 8. Integrasi status pembayaran

Implementasi payment gateway dikerjakan kelompok lain. Sebelum integrasi, kedua kelompok harus menetapkan:

- Identitas order stabil dan tidak berubah.
- Cara backend memverifikasi pembayaran sukses (callback server-to-server yang ditandatangani, verifikasi status ke provider, atau mekanisme aman yang disetujui).
- Cara menangani callback berulang, status pending, gagal, expired, dan refund.
- Field/endpoint serta secret yang tidak boleh dibagikan ke frontend.
- Aturan kapan stok dikurangi/reservasi dan bagaimana kegagalan dispensing direkonsiliasi.

Jangan menganggap request dari kiosk sebagai bukti pembayaran yang sah.

## 9. MQTT contract

Nama topic di bawah merupakan usulan; sesuaikan dengan topic yang sudah dipakai simulator.

| Arah | Topic konseptual | Kegunaan |
|---|---|---|
| Simulator → backend consumer | `vending/{machine_id}/telemetry` | Suhu, status, last-seen, dan telemetry yang diizinkan. |
| Backend → mesin/simulator | `vending/{machine_id}/command/dispense` | Command dispensing dengan `command_id`, `order_id`, dan slot yang tervalidasi. |
| Simulator → backend consumer | `vending/{machine_id}/event/dispense` | Progress dan hasil command, termasuk command ID. |

Contoh event konseptual:

```json
{
  "machine_id": "VM-01",
  "command_id": "CMD-EXAMPLE-001",
  "order_id": "ORD-12345",
  "slot_code": "A1",
  "status": "done",
  "occurred_at": "2026-10-10T20:30:00Z"
}
```

Gunakan validasi payload, identitas mesin, ACL/credential broker, dan idempotensi pada consumer. Jangan mengurangi stok lagi saat event `done` diterima jika pengurangan stok sudah dicatat setelah pembayaran sukses.

## 10. Alur pengujian integrasi

1. Ambil daftar route aktual dari `php artisan route:list --path=api`.
2. Uji endpoint dengan Postman menggunakan environment variable untuk `base_url` dan token.
3. Pastikan request tanpa token/role yang tepat ditolak pada endpoint privat.
4. Uji produk aktif, produk nonaktif, dan stok kosong.
5. Uji order belum dibayar, pembayaran berhasil, callback duplikat, serta request dispense duplikat.
6. Uji simulator offline, broker putus, JSON invalid, command timeout, sukses, dan gagal.
7. Bagikan kepada kelompok kiosk file Postman Collection dan environment contoh tanpa secret. Sertakan base URL yang dapat diakses lintas laptop, bukan `localhost` milik developer API.
