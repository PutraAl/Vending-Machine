# System Overview

## 1. Project

**Nama:** Sistem Perangkat Lunak Mesin Penjual Makanan Panas Otomatis

Sistem ini merupakan platform vending machine yang digunakan untuk mengelola produk, stok, mesin, transaksi, pembayaran, monitoring kondisi mesin, dan proses dispensing makanan secara otomatis.

Sistem terdiri dari aplikasi backend, dashboard, database, MQTT communication, Python simulator, dan ESP32 firmware.

---

## 2. Main Architecture

```text
                    ┌──────────────────────┐
                    │      Dashboard       │
                    │ Laravel + Livewire   │
                    │      + Tailwind      │
                    └──────────┬───────────┘
                               │
                               │ HTTP / REST API
                               ▼
                    ┌──────────────────────┐
                    │    Laravel Backend   │
                    │                      │
                    │ Business Logic       │
                    │ Authentication       │
                    │ Authorization        │
                    │ REST API             │
                    │ MQTT Integration     │
                    └───────┬───────┬──────┘
                            │       │
                   PostgreSQL       │ MQTT
                            │       │
                   ┌────────▼───┐   │
                   │ PostgreSQL │   │
                   │  Database  │   │
                   └────────────┘   │
                                    ▼
                           ┌─────────────────┐
                           │ Mosquitto Broker│
                           └────────┬────────┘
                                    │
                       ┌────────────┴────────────┐
                       │                         │
                ┌──────▼──────┐           ┌──────▼──────┐
                │   Python    │           │    ESP32    │
                │  Simulator  │           │   Firmware  │
                └─────────────┘           └─────────────┘
```

### Communication

Sistem menggunakan beberapa jalur komunikasi:

- Dashboard berkomunikasi dengan Laravel Backend melalui HTTP/REST API.
- Laravel Backend berkomunikasi dengan PostgreSQL untuk penyimpanan data.
- Laravel Backend berkomunikasi dengan mesin melalui MQTT.
- Mosquitto bertindak sebagai MQTT Broker.
- Python Simulator digunakan untuk mensimulasikan perangkat mesin.
- ESP32 Firmware digunakan sebagai implementasi perangkat keras sebenarnya.

---

## 3. Main Components

### 3.1 Backend

Backend menggunakan:

- Laravel
- PHP
- REST API
- Authentication
- Authorization
- MQTT Integration

Backend bertanggung jawab terhadap:

- Business logic
- Pengelolaan user dan role
- Pengelolaan produk
- Pengelolaan kategori produk
- Pengelolaan mesin
- Pengelolaan slot mesin
- Pengelolaan stok
- Pengelolaan transaksi
- Pengelolaan pembayaran
- Monitoring mesin
- Penyimpanan telemetry
- Penyimpanan error mesin
- Proses dispensing
- Penyediaan REST API

---

### 3.2 Dashboard

Dashboard dibangun menggunakan:

- Laravel Blade
- Livewire
- Tailwind CSS

Dashboard digunakan oleh user internal berdasarkan role.

#### Admin / Super Admin

Memiliki akses penuh terhadap sistem.

Dapat mengelola:

- User
- Role
- Produk
- Kategori
- Mesin
- Slot mesin
- Stok
- Transaksi
- Pembayaran
- Monitoring mesin
- Data telemetry
- Error mesin

#### Technician

Berfokus pada monitoring kondisi mesin.

Dapat melihat:

- Status online/offline mesin
- Temperature
- Machine state
- Kondisi door
- Error mesin
- Telemetry

Technician juga menerima informasi atau notifikasi apabila kondisi mesin mengalami masalah, seperti temperature melebihi batas yang ditentukan.

#### Seller / Operator

Berfokus pada pengelolaan produk dan stok.

Dapat melakukan:

- Pengelolaan produk
- Pengelolaan stok
- Pengelolaan slot mesin
- Monitoring stok mesin

#### Buyer

Buyer merupakan pengguna yang melakukan pembelian makanan melalui mesin.

Buyer tidak membutuhkan dashboard internal sistem.

---

### 3.3 PostgreSQL

PostgreSQL digunakan sebagai database utama sistem.

Database menyimpan data:

#### Business Domain

- Users
- Roles
- Products
- Product Categories
- Orders
- Order Items
- Payments
- Refunds

#### IoT Domain

- Machines
- Machine Slots
- Telemetries
- Machine Errors
- Dispenses

Database digunakan sebagai penyimpanan data utama yang dapat diakses oleh Laravel Backend.

---

### 3.4 MQTT Broker

MQTT Broker menggunakan Mosquitto.

Mosquitto berfungsi sebagai perantara komunikasi antara backend dengan perangkat mesin.

Komunikasi menggunakan konsep publish dan subscribe.

Contoh komunikasi:

```text
Laravel Backend
      │
      │ Publish Command
      ▼
Mosquitto Broker
      │
      ▼
ESP32 / Python Simulator
```

Untuk telemetry:

```text
ESP32 / Python Simulator
      │
      │ Publish Telemetry
      ▼
Mosquitto Broker
      │
      ▼
Laravel Backend
```

---

### 3.5 Python Simulator

Python Simulator digunakan untuk mensimulasikan perilaku mesin vending sebelum menggunakan perangkat keras sebenarnya.

Simulator bertanggung jawab untuk mensimulasikan:

- Machine state
- Temperature
- Stock
- Slot
- Dispensing
- Error
- Telemetry

Simulator berkomunikasi dengan sistem menggunakan MQTT.

Tujuan simulator:

- Menguji komunikasi MQTT
- Menguji machine state machine
- Menguji proses dispensing
- Menguji telemetry
- Menguji error handling
- Menguji integrasi backend dengan mesin

---

### 3.6 ESP32 Firmware

ESP32 merupakan perangkat keras yang digunakan sebagai controller mesin.

ESP32 bertanggung jawab terhadap:

- Membaca sensor
- Mengontrol actuator
- Mengelola machine state
- Mengirim telemetry
- Menerima command
- Menjalankan proses dispensing
- Mengirim error

ESP32 berkomunikasi dengan backend melalui MQTT Broker.

---

## 4. Machine State Machine

Mesin berada dalam kondisi panas secara terus-menerus.

Sistem tidak menggunakan state `HEATING` karena proses pemanasan bukan bagian dari alur dispensing.

State machine utama:

```text
             ┌─────────────┐
             │    IDLE     │
             └──────┬──────┘
                    │
                    ▼
             ┌─────────────┐
             │ VALIDATING  │
             └──────┬──────┘
                    │
                    ▼
             ┌─────────────┐
             │ DISPENSING  │
             └──────┬──────┘
                    │
                    ▼
             ┌─────────────┐
             │    DONE     │
             └──────┬──────┘
                    │
                    ▼
             ┌─────────────┐
             │    IDLE     │
             └─────────────┘
```

Jika terjadi masalah:

```text
VALIDATING ───────► ERROR
DISPENSING ───────► ERROR
ERROR ────────────► IDLE
```

### State Description

#### IDLE

Mesin dalam kondisi siap menerima transaksi atau command baru.

#### VALIDATING

Mesin melakukan validasi sebelum proses dispensing.

Validasi dapat mencakup:

- Ketersediaan produk
- Ketersediaan stok
- Kondisi slot
- Kondisi mesin
- Temperature
- Validitas command

#### DISPENSING

Mesin menjalankan proses pengeluaran makanan dari slot yang dipilih.

#### DONE

Proses dispensing berhasil diselesaikan.

Setelah proses selesai, mesin kembali ke `IDLE`.

#### ERROR

Terjadi masalah dalam proses validasi atau dispensing.

Setelah error ditangani atau proses dihentikan, mesin dapat kembali ke `IDLE`.

---

## 5. Temperature Monitoring

Mesin selalu berada dalam kondisi panas.

Temperature bukan merupakan state machine, tetapi merupakan telemetry atau kondisi mesin yang terus dipantau.

Contoh telemetry:

```json
{
    "temperature": 72.5,
    "state": "IDLE",
    "door": "CLOSED",
    "stock": 5
}
```

Sistem dapat menggunakan temperature threshold untuk mendeteksi kondisi mesin.

Contoh:

```text
Temperature <= Threshold
        │
        ▼
     NORMAL

Temperature > Threshold
        │
        ▼
     WARNING
        │
        ▼
Technician Notification
```

Temperature threshold dapat digunakan untuk memberikan informasi atau notifikasi kepada Technician apabila temperature berada di luar batas yang ditentukan.

---

## 6. MQTT Topic Structure

MQTT menggunakan topic berdasarkan `machine_id`.

Format topic:

```text
vending/machine/{machine_id}/status
vending/machine/{machine_id}/telemetry
vending/machine/{machine_id}/command
vending/machine/{machine_id}/response
vending/machine/{machine_id}/error
```

### Status

Digunakan untuk mengirim status mesin.

```json
{
    "state": "IDLE"
}
```

### Telemetry

Digunakan untuk mengirim data kondisi mesin.

```json
{
    "temperature": 72.5,
    "state": "IDLE",
    "door": "CLOSED",
    "stock": 5
}
```

### Command

Digunakan untuk memberikan perintah kepada mesin.

Contoh command dispensing:

```json
{
    "command": "DISPENSE",
    "slot": "A01"
}
```

### Response

Digunakan untuk memberikan response dari mesin terhadap command yang diterima.

### Error

Digunakan untuk mengirim informasi error dari mesin.

Contoh:

```json
{
    "error": "TEMPERATURE_HIGH"
}
```

---

## 7. Main User Roles

Sistem memiliki tiga role utama untuk dashboard:

```text
ADMIN / SUPER ADMIN
        │
        ├── Full System Management
        │
        ├── User Management
        ├── Product Management
        ├── Machine Management
        ├── Transaction Management
        └── Monitoring

TECHNICIAN
        │
        ├── Machine Monitoring
        ├── Temperature Monitoring
        ├── Error Monitoring
        └── Machine Status

SELLER / OPERATOR
        │
        ├── Product Management
        ├── Stock Management
        └── Machine Slot Management
```

Buyer tidak termasuk sebagai role dashboard internal.

Buyer hanya melakukan pembelian melalui vending machine.

---

## 8. Main Business Flow

Alur utama pembelian:

```text
Buyer
  │
  ▼
Select Product
  │
  ▼
Check Product & Stock
  │
  ▼
Create Order
  │
  ▼
Payment
  │
  ▼
Payment Confirmed
  │
  ▼
Send DISPENSE Command
  │
  ▼
Machine VALIDATING
  │
  ▼
Machine DISPENSING
  │
  ▼
Machine DONE
  │
  ▼
Order Completed
```

Jika terjadi error:

```text
Machine
   │
   ▼
ERROR
   │
   ├── Send Error via MQTT
   │
   ▼
Laravel Backend
   │
   ├── Save Machine Error
   │
   └── Notify Technician
```

---

## 9. Development Architecture

Project menggunakan struktur monorepo.

```text
Vending-Machine-System/
│
├── backend/
│   └── Laravel Application
│
├── simulator/
│   └── Python MQTT Simulator
│
├── firmware/
│   └── ESP32 Firmware
│
├── mosquitto/
│   └── MQTT Broker Configuration
│
├── docs/
│   ├── SYSTEM_OVERVIEW.md
│   ├── DATABASE_SCHEMA.md
│   ├── API_SPECIFICATION.md
│   ├── MQTT_CONTRACT.md
│   ├── ROLE_PERMISSION.md
│   └── CODING_GUIDELINE.md
│
├── docker-compose.yml
└── README.md
```

### Backend Structure

Laravel menjadi pusat aplikasi sistem.

```text
backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── artisan
├── composer.json
└── package.json
```

Database migration, model, controller, service, API, Blade, Livewire, dan asset frontend berada di dalam backend Laravel.

---

## 10. Development Principles

Pengembangan sistem mengikuti beberapa prinsip:

### 10.1 API First

Backend menyediakan REST API yang dapat digunakan oleh sistem atau client lain.

### 10.2 MQTT for Machine Communication

Komunikasi antara backend dengan mesin menggunakan MQTT.

### 10.3 Modular Architecture

Setiap bagian sistem dipisahkan berdasarkan tanggung jawabnya:

- Backend
- Simulator
- Firmware
- MQTT
- Database
- Documentation

### 10.4 Source of Truth

Dokumentasi pada folder `docs/` digunakan sebagai referensi utama dalam pengembangan sistem.

Dokumen harus diperbarui apabila terdapat perubahan pada:

- Architecture
- Database
- API
- MQTT
- Role dan Permission
- Coding Guideline

### 10.5 Simulation Before Hardware

Python Simulator digunakan untuk melakukan pengujian awal sebelum integrasi dengan ESP32 dan hardware sebenarnya.

---

## 11. Source of Truth

Dokumen berikut menjadi referensi utama project:

```text
docs/
├── SYSTEM_OVERVIEW.md
├── DATABASE_SCHEMA.md
├── API_SPECIFICATION.md
├── MQTT_CONTRACT.md
├── ROLE_PERMISSION.md
└── CODING_GUIDELINE.md
```

Setiap perubahan besar pada sistem harus diperbarui pada dokumentasi yang relevan.

Tujuannya agar seluruh anggota tim memiliki pemahaman arsitektur yang sama dan implementasi antar-komponen tetap konsisten.