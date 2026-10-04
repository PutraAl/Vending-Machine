# Role Permission

## 1. Overview

Dokumen ini mendefinisikan role dan permission yang digunakan pada sistem vending machine.

Role digunakan untuk membatasi akses user terhadap fitur dashboard dan REST API.

Role disimpan langsung pada tabel `users` melalui field:

```text
users.role
```

Tidak menggunakan tabel `roles` atau `user_roles` pada versi core system.

---

# 2. Available Roles

Sistem memiliki tiga role internal:

```text
admin
technician
operator
```

### Role Mapping

| Database Role | Display Name | Description |
|---|---|---|
| `admin` | Admin / Super Admin | Memiliki akses penuh terhadap sistem |
| `technician` | Technician / Teknisi | Berfokus pada monitoring dan kondisi mesin |
| `operator` | Seller / Operator | Berfokus pada produk dan stok |

Buyer tidak menjadi role dashboard internal.

---

# 3. Admin / Super Admin

## Role

```text
admin
```

Admin memiliki akses penuh terhadap sistem.

Admin bertanggung jawab terhadap pengelolaan seluruh data dan konfigurasi operasional sistem.

## Permissions

### User Management

```text
✓ View users
✓ Create users
✓ Update users
✓ Delete users
✓ Change user role
```

Admin dapat mengubah role user menjadi:

```text
admin
technician
operator
```

---

### Product Management

```text
✓ View products
✓ Create products
✓ Update products
✓ Delete products
✓ Activate / deactivate product
```

---

### Category Management

```text
✓ View categories
✓ Create categories
✓ Update categories
✓ Delete categories
```

---

### Machine Management

```text
✓ View machines
✓ Create machines
✓ Update machines
✓ Delete machines
```

---

### Machine Slot Management

```text
✓ View slots
✓ Create slots
✓ Update slots
✓ Delete slots
✓ Manage stock
```

---

### Order Management

```text
✓ View orders
✓ View order details
✓ View order status
```

Order creation untuk pembelian normal dapat berasal dari client atau proses transaksi, sedangkan Admin memiliki akses untuk melihat dan memantau order.

---

### Machine Monitoring

```text
✓ View machine status
✓ View temperature
✓ View telemetry
✓ View machine errors
✓ View dispense status
```

---

### Error Management

```text
✓ View errors
✓ View error details
✓ Resolve errors
```

---

# 4. Technician

## Role

```text
technician
```

Technician berfokus pada kondisi dan operasional mesin.

Technician tidak bertanggung jawab terhadap pengelolaan business data seperti user dan produk.

## Permissions

### User Management

```text
✗ View users
✗ Create users
✗ Update users
✗ Delete users
```

---

### Product Management

```text
✗ Create products
✗ Update products
✗ Delete products
```

Technician dapat melihat informasi produk yang diperlukan untuk monitoring mesin.

```text
✓ View products
```

---

### Category Management

```text
✗ Create categories
✗ Update categories
✗ Delete categories
```

---

### Machine Management

Technician dapat melihat informasi mesin, tetapi tidak mengubah konfigurasi utama mesin.

```text
✓ View machines
✗ Create machines
✗ Update machines
✗ Delete machines
```

---

### Machine Slot

```text
✓ View slots
✗ Create slots
✗ Update slots
✗ Delete slots
```

Technician dapat melihat kondisi slot untuk membantu troubleshooting.

---

### Stock

Technician dapat melihat kondisi stok untuk kebutuhan monitoring.

```text
✓ View stock
✗ Modify stock
```

---

### Order

```text
✓ View orders
✓ View order details
✓ View order status
```

---

### Machine Monitoring

```text
✓ View machine status
✓ View temperature
✓ View telemetry
✓ View machine errors
✓ View dispense status
```

---

### Error Management

```text
✓ View errors
✓ View error details
✓ Resolve errors
```

---

### Notifications

Technician menerima informasi atau notification ketika terdapat kondisi mesin yang membutuhkan perhatian.

Contoh:

```text
TEMPERATURE_HIGH
DISPENSER_JAM
MACHINE_OFFLINE
```

---

# 5. Operator / Seller

## Role

```text
operator
```

Operator berfokus pada pengelolaan produk dan stok mesin.

## Permissions

### User Management

```text
✗ View users
✗ Create users
✗ Update users
✗ Delete users
```

---

### Product Management

```text
✓ View products
✓ Create products
✓ Update products
✓ Activate / deactivate product
✗ Delete products
```

Penghapusan produk dapat dibatasi agar histori transaksi tetap aman.

Apabila produk sudah pernah digunakan pada transaksi, produk sebaiknya dinonaktifkan daripada dihapus secara permanen.

---

### Category Management

```text
✓ View categories
✓ Create categories
✓ Update categories
✗ Delete categories
```

---

### Machine Management

Operator hanya membutuhkan akses monitoring dasar.

```text
✓ View machines
✗ Create machines
✗ Update machines
✗ Delete machines
```

---

### Machine Slot

```text
✓ View slots
✓ Manage product assignment
✓ Manage stock
✗ Create machine
✗ Delete machine
```

---

### Stock

```text
✓ View stock
✓ Update stock
```

Operator bertanggung jawab memastikan stok pada slot sesuai dengan kondisi operasional mesin.

Business rule:

```text
0 <= stock <= capacity
```

---

### Order

```text
✓ View orders
✓ View order details
✓ View order status
```

Operator tidak mengubah status pembayaran atau memproses payment.

---

### Machine Monitoring

Operator dapat melihat informasi dasar mesin untuk membantu pengelolaan stok.

```text
✓ View machine status
✓ View temperature
✓ View telemetry
✗ Resolve machine errors
```

---

# 6. Buyer

Buyer tidak termasuk role dashboard internal.

Buyer berinteraksi dengan vending machine untuk melakukan pembelian.

Aktivitas buyer:

```text
Select Product
      │
      ▼
Check Availability
      │
      ▼
Payment
      │
      ▼
Receive Product
```

Payment ditangani oleh sistem/kelompok payment di luar core system.

Buyer tidak memiliki akses ke:

```text
Admin Dashboard
Technician Dashboard
Operator Dashboard
```

---

# 7. Permission Matrix

| Feature | Admin | Technician | Operator |
|---|---:|---:|---:|
| View Users | ✓ | ✗ | ✗ |
| Create Users | ✓ | ✗ | ✗ |
| Update Users | ✓ | ✗ | ✗ |
| Delete Users | ✓ | ✗ | ✗ |
| Change User Role | ✓ | ✗ | ✗ |
| View Categories | ✓ | ✓ | ✓ |
| Create Categories | ✓ | ✗ | ✓ |
| Update Categories | ✓ | ✗ | ✓ |
| Delete Categories | ✓ | ✗ | ✗ |
| View Products | ✓ | ✓ | ✓ |
| Create Products | ✓ | ✗ | ✓ |
| Update Products | ✓ | ✗ | ✓ |
| Delete Products | ✓ | ✗ | ✗ |
| Activate Product | ✓ | ✗ | ✓ |
| View Machines | ✓ | ✓ | ✓ |
| Create Machines | ✓ | ✗ | ✗ |
| Update Machines | ✓ | ✗ | ✗ |
| Delete Machines | ✓ | ✗ | ✗ |
| View Machine Slots | ✓ | ✓ | ✓ |
| Manage Machine Slots | ✓ | ✗ | ✓ |
| View Stock | ✓ | ✓ | ✓ |
| Update Stock | ✓ | ✗ | ✓ |
| View Orders | ✓ | ✓ | ✓ |
| View Order Detail | ✓ | ✓ | ✓ |
| View Machine Status | ✓ | ✓ | ✓ |
| View Temperature | ✓ | ✓ | ✓ |
| View Telemetry | ✓ | ✓ | ✓ |
| View Machine Errors | ✓ | ✓ | ✓ |
| Resolve Machine Errors | ✓ | ✓ | ✗ |
| View Dispense Status | ✓ | ✓ | ✓ |
| Receive Notifications | ✓ | ✓ | ✓ |

---

# 8. Resource Access Summary

## Users

```text
Admin
    │
    └── Full CRUD

Technician
    │
    └── No access

Operator
    │
    └── No access
```

---

## Products

```text
Admin
    │
    └── Full CRUD

Technician
    │
    └── Read Only

Operator
    │
    └── Create / Read / Update
```

---

## Machines

```text
Admin
    │
    └── Full CRUD

Technician
    │
    └── Read Only + Monitoring

Operator
    │
    └── Read Only
```

---

## Stock

```text
Admin
    │
    └── Full Management

Technician
    │
    └── Read Only

Operator
    │
    └── View + Update
```

---

## Machine Errors

```text
Admin
    │
    └── View + Resolve

Technician
    │
    └── View + Resolve

Operator
    │
    └── View Only
```

---

# 9. Authorization Rules

Authorization dilakukan pada backend.

Frontend hanya digunakan untuk menampilkan atau menyembunyikan menu sesuai role.

Security tidak boleh hanya bergantung pada frontend.

Contoh:

```text
User Request
     │
     ▼
Authentication
     │
     ▼
Check Role / Permission
     │
     ├── Allowed
     │      │
     │      ▼
     │   Controller
     │
     └── Denied
            │
            ▼
          403
```

---

# 10. Role-Based Route Access

Contoh struktur route:

```text
/admin/*
    → admin

/technician/*
    → technician

/operator/*
    → operator
```

Untuk endpoint yang dapat digunakan beberapa role:

```text
/api/products
    → admin
    → technician (read)
    → operator

/api/machines
    → admin
    → technician (read)
    → operator (read)

/api/machines/{machine}/telemetry
    → admin
    → technician
    → operator (read)
```

Detail endpoint tetap mengikuti:

```text
docs/API_SPECIFICATION.md
```

---

# 11. Dashboard Navigation

## Admin Dashboard

Menu utama:

```text
Dashboard
Users
Products
Categories
Machines
Machine Slots
Stock
Orders
Monitoring
Telemetry
Errors
Dispenses
```

---

## Technician Dashboard

Menu utama:

```text
Dashboard
Machines
Machine Status
Temperature
Telemetry
Machine Errors
Dispense Monitoring
Notifications
```

---

## Operator Dashboard

Menu utama:

```text
Dashboard
Products
Categories
Machines
Machine Slots
Stock
Orders
Machine Status
```

---

# 12. UI Visibility Rules

Menu dashboard disesuaikan dengan role.

Contoh:

```text
Admin
    → melihat seluruh menu

Technician
    → hanya melihat menu monitoring

Operator
    → hanya melihat menu product dan stock
```

Namun, menyembunyikan menu bukan merupakan authorization.

Backend tetap wajib memeriksa permission pada setiap protected request.

---

# 13. Role Storage

Role disimpan langsung pada tabel `users`.

Contoh:

```text
users

id | name       | email              | role
---|------------|--------------------|----------
1  | Admin      | admin@example.com  | admin
2  | Technician | tech@example.com   | technician
3  | Operator   | op@example.com     | operator
```

Tidak terdapat:

```text
roles
user_roles
```

pada core database.

---

# 14. Role Validation

Nilai `users.role` hanya boleh menggunakan:

```text
admin
technician
operator
```

Nilai lain harus ditolak oleh backend.

Contoh invalid:

```text
manager
seller
staff
superuser
```

Jika ingin menampilkan nama `Super Admin`, gunakan label pada UI:

```text
Database:
admin

Display:
Admin / Super Admin
```

---

# 15. Role Modification

Hanya Admin yang dapat mengubah role user.

Contoh:

```text
Admin
   │
   ▼
User Management
   │
   ▼
Change Role
   │
   ├── admin
   ├── technician
   └── operator
```

Technician dan Operator tidak dapat mengubah role user.

---

# 16. Critical Actions

Beberapa tindakan dianggap sebagai critical action.

### Admin Only

```text
Delete User
Change User Role
Delete Machine
Delete Product
Delete Category
```

### Admin / Operator

```text
Update Product
Update Product Status
Update Stock
Manage Machine Slot
```

### Admin / Technician

```text
Resolve Machine Error
```

---

# 17. Business Rules by Role

## Admin

Admin bertanggung jawab terhadap:

```text
System Administration
Data Management
User Management
Machine Management
Monitoring
```

## Technician

Technician bertanggung jawab terhadap:

```text
Machine Monitoring
Temperature Monitoring
Telemetry
Error Handling
Operational Troubleshooting
```

## Operator

Operator bertanggung jawab terhadap:

```text
Product Management
Stock Management
Machine Slot Management
Product Availability
```

---

# 18. Separation of Responsibilities

Setiap role memiliki fokus yang berbeda.

```text
                     SYSTEM
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
      ADMIN         TECHNICIAN       OPERATOR
        │               │               │
        ▼               ▼               ▼
   Management       Monitoring     Product & Stock
```

Tujuan pembagian role adalah mengurangi akses yang tidak diperlukan dan menjaga tanggung jawab masing-masing user.

---

# 19. Security Principle

Sistem menggunakan prinsip **least privilege**.

User hanya memperoleh akses yang dibutuhkan untuk menjalankan tanggung jawabnya.

Contoh:

```text
Technician
    ✗ Tidak dapat mengubah user
    ✗ Tidak dapat mengubah produk
    ✓ Dapat memonitor mesin

Operator
    ✗ Tidak dapat mengubah user
    ✓ Dapat mengelola produk dan stok
    ✓ Dapat melihat mesin

Admin
    ✓ Full Access
```

---

# 20. Future Extension

Role system dapat dikembangkan apabila kebutuhan sistem bertambah.

Contoh role tambahan di masa depan:

```text
super_admin
warehouse
manager
finance
```

Jika sistem membutuhkan permission yang lebih kompleks, arsitektur dapat dikembangkan menjadi:

```text
users
   │
   ▼
roles
   │
   ▼
permissions
```

Namun mekanisme tersebut tidak digunakan pada core system versi awal.

---

# 21. Source of Truth

Dokumen ini menjadi referensi utama mengenai:

- Role
- Permission
- Authorization
- Dashboard access
- Protected actions
- Role-based route access

Implementasi middleware, controller authorization, API protection, dan dashboard navigation harus mengikuti aturan yang didefinisikan pada dokumen ini.

Perubahan permission harus diperbarui pada dokumen ini sebelum diterapkan ke sistem.