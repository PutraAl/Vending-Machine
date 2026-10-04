# Database Schema

## 1. Overview

Database digunakan sebagai pusat penyimpanan data untuk sistem vending machine.

Database menggunakan **PostgreSQL** dan dikelola melalui Laravel Backend.

Database dibagi menjadi dua domain utama:

### Business Domain

Digunakan untuk mengelola:

- User
- Product
- Category
- Machine
- Machine Slot
- Order
- Order Item

### IoT Domain

Digunakan untuk mengelola:

- Machine Telemetry
- Machine Error
- Dispensing

---

## 2. Database Architecture

```text
                        PostgreSQL
                             │
          ┌──────────────────┴──────────────────┐
          │                                     │
    BUSINESS DOMAIN                        IOT DOMAIN
          │                                     │
    ┌─────┴──────────┐                  ┌───────┴────────┐
    │                │                  │                │
   Users          Products           Telemetries      Errors
    │                │                  │                │
    │          Categories               │                │
    │                                   │                │
    └───────► Orders ◄──────────── Machines ◄────────────┘
                  │                     │
                  │                     │
             Order Items          Machine Slots
                                        │
                                        ▼
                                   Dispenses
```

---

# 3. Tables

## 3.1 users

Tabel `users` digunakan untuk menyimpan data pengguna internal sistem.

Role disimpan langsung sebagai atribut pada tabel `users`.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID user |
| name | VARCHAR(255) | NOT NULL | Nama user |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Email user |
| password | VARCHAR(255) | NOT NULL | Password yang telah di-hash |
| role | VARCHAR(50) | NOT NULL | Role user |
| created_at | TIMESTAMP | NULL | Waktu pembuatan data |
| updated_at | TIMESTAMP | NULL | Waktu perubahan data |

### Allowed Roles

```text
admin
technician
operator
```

### Role Description

| Role | Description |
|---|---|
| admin | Memiliki akses penuh terhadap sistem |
| technician | Berfokus pada monitoring kondisi mesin |
| operator | Mengelola produk, slot, dan stok |

Buyer tidak menjadi role dashboard internal.

---

## 3.2 product_categories

Tabel `product_categories` digunakan untuk mengelompokkan produk.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID kategori |
| name | VARCHAR(100) | UNIQUE, NOT NULL | Nama kategori |
| description | TEXT | NULL | Deskripsi kategori |
| created_at | TIMESTAMP | NULL | Waktu pembuatan |
| updated_at | TIMESTAMP | NULL | Waktu perubahan |

### Example

```text
Makanan
Minuman
Snack
```

---

## 3.3 products

Tabel `products` digunakan untuk menyimpan data produk yang dapat dijual melalui vending machine.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID produk |
| category_id | BIGINT | FK, NOT NULL | ID kategori |
| name | VARCHAR(255) | NOT NULL | Nama produk |
| description | TEXT | NULL | Deskripsi produk |
| price | NUMERIC(12,2) | NOT NULL | Harga produk |
| image | VARCHAR(255) | NULL | Path atau URL gambar |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Status produk |
| created_at | TIMESTAMP | NULL | Waktu pembuatan |
| updated_at | TIMESTAMP | NULL | Waktu perubahan |

### Relationship

```text
product_categories
        │
        │ 1:N
        ▼
     products
```

Satu kategori dapat memiliki banyak produk.

Satu produk hanya memiliki satu kategori.

---

## 3.4 machines

Tabel `machines` digunakan untuk menyimpan data vending machine.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID mesin |
| machine_code | VARCHAR(50) | UNIQUE, NOT NULL | Kode unik mesin |
| name | VARCHAR(100) | NOT NULL | Nama mesin |
| location | VARCHAR(255) | NULL | Lokasi mesin |
| status | VARCHAR(50) | NOT NULL | Status koneksi/kondisi mesin |
| temperature_threshold | NUMERIC(5,2) | NOT NULL | Batas temperature mesin |
| created_at | TIMESTAMP | NULL | Waktu pembuatan |
| updated_at | TIMESTAMP | NULL | Waktu perubahan |

### Example

```text
machine_code:
VM001

name:
Vending Machine Lobby

location:
Gedung A - Lantai 1

temperature_threshold:
80.00
```

### Machine Status

Status machine dapat digunakan untuk menunjukkan kondisi koneksi mesin.

Contoh:

```text
ONLINE
OFFLINE
```

Status operasional seperti:

```text
IDLE
VALIDATING
DISPENSING
DONE
ERROR
```

disimpan sebagai state mesin pada telemetry atau data status mesin, bukan sebagai status koneksi.

---

## 3.5 machine_slots

Tabel `machine_slots` digunakan untuk menghubungkan produk dengan slot tertentu pada mesin.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID slot |
| machine_id | BIGINT | FK, NOT NULL | ID mesin |
| product_id | BIGINT | FK, NOT NULL | ID produk |
| slot_code | VARCHAR(20) | NOT NULL | Kode slot |
| stock | INTEGER | NOT NULL, DEFAULT 0 | Jumlah stok saat ini |
| capacity | INTEGER | NOT NULL | Kapasitas maksimum slot |
| created_at | TIMESTAMP | NULL | Waktu pembuatan |
| updated_at | TIMESTAMP | NULL | Waktu perubahan |

### Relationship

```text
machines
    │
    │ 1:N
    ▼
machine_slots
    │
    │ N:1
    ▼
products
```

Satu mesin memiliki banyak slot.

Satu slot berada pada satu mesin.

Satu slot berisi satu produk pada satu waktu.

### Example

```text
Machine VM001

A01 → Nasi Goreng → Stock: 5 / 10
A02 → Mie Goreng  → Stock: 3 / 10
A03 → Ayam        → Stock: 7 / 10
```

### Business Rules

```text
stock >= 0
stock <= capacity
```

Stok tidak boleh bernilai negatif dan tidak boleh melebihi kapasitas slot.

---

# 4. Order Domain

## 4.1 orders

Tabel `orders` digunakan untuk menyimpan transaksi pembelian yang terjadi pada vending machine.

Sistem vending machine hanya menangani data order dan status proses dispensing.

Payment diproses oleh sistem/kelompok lain dan berada di luar scope core database.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID order |
| order_code | VARCHAR(50) | UNIQUE, NOT NULL | Kode unik order |
| machine_id | BIGINT | FK, NOT NULL | Mesin tempat pembelian |
| status | VARCHAR(50) | NOT NULL | Status order |
| total_amount | NUMERIC(12,2) | NOT NULL | Total harga order |
| created_at | TIMESTAMP | NULL | Waktu pembuatan |
| updated_at | TIMESTAMP | NULL | Waktu perubahan |

### Order Status

```text
PENDING
READY_TO_DISPENSE
DISPENSING
COMPLETED
FAILED
```

### Status Flow

```text
PENDING
   │
   ▼
READY_TO_DISPENSE
   │
   ▼
DISPENSING
   │
   ▼
COMPLETED
```

Jika terjadi masalah:

```text
PENDING ─────────► FAILED

READY_TO_DISPENSE ──► FAILED

DISPENSING ───────► FAILED
```

### Payment Integration

Payment tidak dikelola oleh tabel `payments` pada core system.

Kelompok payment akan melakukan proses pembayaran dan memberikan informasi bahwa pembayaran telah berhasil.

Setelah pembayaran berhasil, order dapat berubah menjadi:

```text
PENDING
   │
   │ Payment Success
   ▼
READY_TO_DISPENSE
```

Integrasi detail dengan kelompok payment akan ditentukan melalui REST API contract.

---

## 4.2 order_items

Tabel `order_items` digunakan untuk menyimpan detail produk dalam sebuah order.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID order item |
| order_id | BIGINT | FK, NOT NULL | ID order |
| product_id | BIGINT | FK, NOT NULL | ID produk |
| slot_id | BIGINT | FK, NOT NULL | ID slot |
| quantity | INTEGER | NOT NULL | Jumlah produk |
| price | NUMERIC(12,2) | NOT NULL | Harga produk saat transaksi |
| subtotal | NUMERIC(12,2) | NOT NULL | Total harga item |
| created_at | TIMESTAMP | NULL | Waktu pembuatan |
| updated_at | TIMESTAMP | NULL | Waktu perubahan |

### Relationship

```text
orders
   │
   │ 1:N
   ▼
order_items
   │
   ├────────► products
   │
   └────────► machine_slots
```

### Calculation

```text
subtotal = quantity × price
```

Total order:

```text
total_amount = SUM(order_items.subtotal)
```

Harga pada `order_items.price` disimpan sebagai snapshot harga pada saat order dibuat.

Hal ini mencegah perubahan harga produk memengaruhi histori transaksi.

---

# 5. IoT Domain

## 5.1 telemetries

Tabel `telemetries` digunakan untuk menyimpan data kondisi mesin yang dikirim melalui MQTT.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID telemetry |
| machine_id | BIGINT | FK, NOT NULL | ID mesin |
| temperature | NUMERIC(5,2) | NULL | Temperature mesin |
| state | VARCHAR(50) | NOT NULL | State mesin |
| door_status | VARCHAR(20) | NULL | Kondisi door |
| created_at | TIMESTAMP | NOT NULL | Waktu telemetry diterima |

### Machine State

State machine utama:

```text
IDLE
VALIDATING
DISPENSING
DONE
ERROR
```

### Door Status

Contoh:

```text
OPEN
CLOSED
```

### Example Telemetry

```json
{
    "temperature": 72.5,
    "state": "IDLE",
    "door": "CLOSED"
}
```

Telemetry digunakan untuk monitoring dan histori kondisi mesin.

---

## 5.2 machine_errors

Tabel `machine_errors` digunakan untuk mencatat error yang terjadi pada mesin.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID error |
| machine_id | BIGINT | FK, NOT NULL | ID mesin |
| error_code | VARCHAR(100) | NOT NULL | Kode error |
| message | TEXT | NOT NULL | Pesan error |
| severity | VARCHAR(20) | NOT NULL | Tingkat severity |
| created_at | TIMESTAMP | NOT NULL | Waktu error |
| resolved_at | TIMESTAMP | NULL | Waktu error diselesaikan |

### Example Error Code

```text
TEMPERATURE_HIGH
DISPENSER_JAM
SLOT_EMPTY
MQTT_ERROR
```

### Severity

```text
INFO
WARNING
CRITICAL
```

### Error Flow

```text
Machine
   │
   │ MQTT Error
   ▼
Laravel Backend
   │
   ▼
machine_errors
   │
   ▼
Technician Monitoring
```

---

## 5.3 dispenses

Tabel `dispenses` digunakan untuk mencatat proses pengeluaran produk dari mesin.

### Columns

| Column | Type | Constraint | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | ID dispense |
| order_id | BIGINT | FK, NOT NULL | ID order |
| machine_id | BIGINT | FK, NOT NULL | ID mesin |
| slot_id | BIGINT | FK, NOT NULL | ID slot |
| status | VARCHAR(50) | NOT NULL | Status dispensing |
| started_at | TIMESTAMP | NULL | Waktu proses dimulai |
| completed_at | TIMESTAMP | NULL | Waktu proses selesai |
| created_at | TIMESTAMP | NULL | Waktu pembuatan record |
| updated_at | TIMESTAMP | NULL | Waktu perubahan record |

### Dispense Status

```text
PENDING
DISPENSING
COMPLETED
FAILED
```

### Dispense Flow

```text
READY_TO_DISPENSE
        │
        ▼
     DISPENSE
        │
        ▼
   DISPENSING
        │
        ├──────────────► FAILED
        │
        ▼
    COMPLETED
```

---

# 6. Entity Relationships

Relationship utama database:

```text
users
  │
  └── role

product_categories
  │
  └── products
          │
          │
          └──────────────┐
                         │
machines ── machine_slots
    │                    │
    │                    │
    │                    └── products
    │
    ├── telemetries
    │
    ├── machine_errors
    │
    ├── orders
    │      │
    │      └── order_items
    │              │
    │              ├── products
    │              └── machine_slots
    │
    └── dispenses
           │
           ├── orders
           └── machine_slots
```

---

# 7. Foreign Key Relationships

| Table | Foreign Key | References |
|---|---|---|
| products | category_id | product_categories.id |
| machine_slots | machine_id | machines.id |
| machine_slots | product_id | products.id |
| orders | machine_id | machines.id |
| order_items | order_id | orders.id |
| order_items | product_id | products.id |
| order_items | slot_id | machine_slots.id |
| telemetries | machine_id | machines.id |
| machine_errors | machine_id | machines.id |
| dispenses | order_id | orders.id |
| dispenses | machine_id | machines.id |
| dispenses | slot_id | machine_slots.id |

---

# 8. Relationship Summary

### Product Category → Products

```text
1 Category
   │
   └─── N Products
```

### Machine → Machine Slots

```text
1 Machine
   │
   └─── N Slots
```

### Product → Machine Slots

```text
1 Product
   │
   └─── N Machine Slots
```

Dalam implementasi normal, satu produk dapat ditempatkan pada beberapa slot atau mesin.

### Machine → Orders

```text
1 Machine
   │
   └─── N Orders
```

### Order → Order Items

```text
1 Order
   │
   └─── N Order Items
```

### Machine → Telemetries

```text
1 Machine
   │
   └─── N Telemetries
```

### Machine → Errors

```text
1 Machine
   │
   └─── N Errors
```

### Order → Dispenses

```text
1 Order
   │
   └─── N Dispenses
```

---

# 9. Core Business Rules

## 9.1 Product

- Product harus memiliki kategori.
- Product memiliki harga.
- Product dapat diaktifkan atau dinonaktifkan.
- Product yang tidak aktif tidak dapat digunakan untuk transaksi baru.

## 9.2 Machine Slot

- Setiap slot harus dimiliki oleh satu machine.
- Setiap slot mengacu pada satu product.
- Stock tidak boleh negatif.
- Stock tidak boleh melebihi capacity.

## 9.3 Order

- Setiap order memiliki `order_code` unik.
- Order harus terkait dengan machine.
- Order memiliki minimal satu order item.
- Total order berasal dari total subtotal order item.
- Order hanya dapat masuk proses dispensing setelah status siap untuk dispense.

## 9.4 Dispensing

- Dispensing harus memiliki order.
- Dispensing harus memiliki machine.
- Dispensing harus memiliki slot.
- Stock harus tersedia sebelum dispensing.
- Jika dispensing berhasil, stock slot dikurangi.
- Jika dispensing gagal, sistem mencatat error dan order dapat berubah menjadi `FAILED`.

## 9.5 Telemetry

- Telemetry harus terkait dengan machine.
- Data telemetry dikirim oleh machine melalui MQTT.
- Telemetry digunakan untuk monitoring dan histori kondisi mesin.

## 9.6 Machine Error

- Error harus terkait dengan machine.
- Error yang belum diselesaikan memiliki `resolved_at = NULL`.
- Error dengan severity tinggi dapat digunakan sebagai dasar notifikasi kepada Technician.

---

# 10. Database Scope

Database versi awal hanya mencakup core vending machine system.

### Included

```text
✓ Users
✓ Role management melalui users.role
✓ Product
✓ Product Category
✓ Machine
✓ Machine Slot
✓ Stock
✓ Order
✓ Order Item
✓ Telemetry
✓ Machine Error
✓ Dispense
```

### Not Included

Fitur berikut berada di luar scope core system:

```text
✗ Payment Processing
✗ Payment Gateway
✗ Payment UI
✗ Refund System
✗ AI Nutritional Analysis
✗ Product Nutrition Database
```

Fitur tersebut dapat ditambahkan pada pengembangan berikutnya melalui integrasi dengan sistem eksternal atau penambahan module baru.

---

# 11. Future Extension

Database dirancang agar dapat dikembangkan tanpa mengubah core architecture secara besar.

Contoh pengembangan berikutnya:

```text
Current Core
     │
     ├──────────────► Payment Integration
     │
     ├──────────────► AI Nutrition
     │
     ├──────────────► Notification System
     │
     └──────────────► Advanced Analytics
```

Contoh kemungkinan tabel tambahan di masa depan:

```text
payments
product_nutrition
refunds
notifications
```

Tabel tersebut **tidak menjadi bagian dari database core versi awal**.

---

# 12. Source of Truth

Dokumen ini menjadi referensi utama untuk struktur database.

Setiap perubahan terhadap:

- Table
- Column
- Data Type
- Primary Key
- Foreign Key
- Relationship
- Business Rule

harus diperbarui pada dokumen ini sebelum atau bersamaan dengan perubahan implementasi database.

Laravel migrations dan Eloquent Models harus mengikuti struktur yang telah didefinisikan pada dokumen ini.