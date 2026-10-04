# API Specification

## 1. Overview

REST API digunakan sebagai jalur komunikasi utama antara Laravel Backend dengan client atau sistem lain.

API digunakan untuk:

- Dashboard
- Integrasi dengan kelompok lain
- Product management
- Machine management
- Stock management
- Order management
- Machine monitoring
- Dispensing

API tidak menangani:

- Payment processing
- Payment gateway
- AI nutritional analysis

Payment hanya diintegrasikan melalui endpoint konfirmasi pembayaran.

---

# 2. Base URL

Development:

```text
http://localhost:8000/api
```

Contoh:

```text
http://localhost:8000/api/products
```

Production:

```text
https://<domain>/api
```

---

# 3. API Design Principles

API menggunakan prinsip REST.

### HTTP Methods

| Method | Usage |
|---|---|
| GET | Mengambil data |
| POST | Membuat data atau menjalankan action |
| PUT | Memperbarui seluruh data |
| PATCH | Memperbarui sebagian data |
| DELETE | Menghapus data |

### Response Format

Response API menggunakan JSON.

Contoh success:

```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": {}
}
```

Contoh error:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {}
}
```

---

# 4. Authentication & Authorization

API memiliki akses berdasarkan user dan role.

Role yang digunakan:

```text
admin
technician
operator
```

Buyer tidak memiliki akses ke dashboard internal.

### Role Access

| Resource | Admin | Technician | Operator |
|---|---:|---:|---:|
| Users | ✓ | - | - |
| Categories | ✓ | - | ✓ |
| Products | ✓ | View | ✓ |
| Machines | ✓ | View | View |
| Machine Slots | ✓ | View | ✓ |
| Stock | ✓ | View | ✓ |
| Orders | ✓ | View | View |
| Telemetry | ✓ | ✓ | View |
| Errors | ✓ | ✓ | View |
| Dispense | ✓ | - | - |

Authentication mechanism dapat menggunakan Laravel authentication/token mechanism sesuai implementasi backend.

Detail package authentication tidak menjadi bagian dari API contract ini.

---

# 5. API Endpoint Structure

```text
/api
│
├── /auth
│
├── /users
│
├── /categories
│
├── /products
│
├── /machines
│   ├── /{machine}
│   ├── /{machine}/slots
│   ├── /{machine}/status
│   ├── /{machine}/telemetry
│   └── /{machine}/errors
│
├── /orders
│   └── /{order}
│
├── /dispenses
│
└── /integrations
    └── /payment
```

---

# 6. Authentication API

## 6.1 Login

```http
POST /api/auth/login
```

Digunakan untuk login user dashboard.

### Request

```json
{
    "email": "admin@example.com",
    "password": "password"
}
```

### Response

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Admin",
            "email": "admin@example.com",
            "role": "admin"
        },
        "token": "..."
    }
}
```

> Bentuk token dapat disesuaikan dengan mekanisme authentication yang digunakan pada implementasi Laravel.

---

## 6.2 Logout

```http
POST /api/auth/logout
```

Response:

```json
{
    "success": true,
    "message": "Logout successful"
}
```

---

# 7. User API

## 7.1 Get Users

```http
GET /api/users
```

Access:

```text
admin
```

---

## 7.2 Get User

```http
GET /api/users/{id}
```

Access:

```text
admin
```

---

## 7.3 Create User

```http
POST /api/users
```

### Request

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "role": "operator"
}
```

---

## 7.4 Update User

```http
PUT /api/users/{id}
```

---

## 7.5 Delete User

```http
DELETE /api/users/{id}
```

---

# 8. Category API

## 8.1 Get Categories

```http
GET /api/categories
```

---

## 8.2 Get Category

```http
GET /api/categories/{id}
```

---

## 8.3 Create Category

```http
POST /api/categories
```

### Request

```json
{
    "name": "Makanan",
    "description": "Kategori makanan"
}
```

---

## 8.4 Update Category

```http
PUT /api/categories/{id}
```

---

## 8.5 Delete Category

```http
DELETE /api/categories/{id}
```

---

# 9. Product API

## 9.1 Get Products

```http
GET /api/products
```

### Query Parameters

```text
?page=1
&per_page=10
&search=nasi
&category_id=1
&is_active=true
```

---

## 9.2 Get Product

```http
GET /api/products/{id}
```

---

## 9.3 Create Product

```http
POST /api/products
```

### Request

```json
{
    "category_id": 1,
    "name": "Nasi Goreng",
    "description": "Nasi goreng hangat",
    "price": 15000,
    "image": "nasi-goreng.jpg",
    "is_active": true
}
```

---

## 9.4 Update Product

```http
PUT /api/products/{id}
```

---

## 9.5 Delete Product

```http
DELETE /api/products/{id}
```

---

# 10. Machine API

## 10.1 Get Machines

```http
GET /api/machines
```

### Query Parameters

```text
?status=ONLINE
&search=VM001
```

---

## 10.2 Get Machine

```http
GET /api/machines/{machine}
```

---

## 10.3 Create Machine

```http
POST /api/machines
```

### Request

```json
{
    "machine_code": "VM001",
    "name": "Vending Machine Lobby",
    "location": "Gedung A - Lantai 1",
    "status": "OFFLINE",
    "temperature_threshold": 80.00
}
```

---

## 10.4 Update Machine

```http
PUT /api/machines/{machine}
```

---

## 10.5 Delete Machine

```http
DELETE /api/machines/{machine}
```

---

# 11. Machine Slot API

## 11.1 Get Slots

```http
GET /api/machines/{machine}/slots
```

### Response

```json
{
    "success": true,
    "message": "Slots retrieved successfully",
    "data": [
        {
            "id": 1,
            "slot_code": "A01",
            "product_id": 1,
            "product_name": "Nasi Goreng",
            "stock": 5,
            "capacity": 10
        }
    ]
}
```

---

## 11.2 Create Slot

```http
POST /api/machines/{machine}/slots
```

### Request

```json
{
    "slot_code": "A01",
    "product_id": 1,
    "stock": 5,
    "capacity": 10
}
```

---

## 11.3 Update Slot

```http
PUT /api/machines/{machine}/slots/{slot}
```

---

## 11.4 Delete Slot

```http
DELETE /api/machines/{machine}/slots/{slot}
```

---

## 11.5 Update Stock

```http
PATCH /api/machines/{machine}/slots/{slot}/stock
```

### Request

```json
{
    "stock": 8
}
```

Business rule:

```text
0 <= stock <= capacity
```

---

# 12. Machine Monitoring API

## 12.1 Get Machine Status

```http
GET /api/machines/{machine}/status
```

### Response

```json
{
    "success": true,
    "message": "Machine status retrieved successfully",
    "data": {
        "machine_id": 1,
        "machine_code": "VM001",
        "connection_status": "ONLINE",
        "state": "IDLE",
        "temperature": 72.5,
        "door_status": "CLOSED"
    }
}
```

---

## 12.2 Get Telemetry

```http
GET /api/machines/{machine}/telemetry
```

### Query Parameters

```text
?from=2026-10-01
&to=2026-10-04
&limit=50
```

### Response

```json
{
    "success": true,
    "message": "Telemetry retrieved successfully",
    "data": [
        {
            "temperature": 72.5,
            "state": "IDLE",
            "door_status": "CLOSED",
            "created_at": "2026-10-04T10:30:00Z"
        }
    ]
}
```

---

## 12.3 Get Machine Errors

```http
GET /api/machines/{machine}/errors
```

### Query Parameters

```text
?severity=CRITICAL
&resolved=false
```

### Response

```json
{
    "success": true,
    "message": "Machine errors retrieved successfully",
    "data": [
        {
            "id": 1,
            "error_code": "TEMPERATURE_HIGH",
            "message": "Machine temperature exceeded threshold",
            "severity": "WARNING",
            "created_at": "2026-10-04T10:30:00Z",
            "resolved_at": null
        }
    ]
}
```

---

## 12.4 Resolve Machine Error

```http
PATCH /api/machines/{machine}/errors/{error}/resolve
```

### Response

```json
{
    "success": true,
    "message": "Machine error resolved",
    "data": {
        "id": 1,
        "resolved_at": "2026-10-04T10:45:00Z"
    }
}
```

---

# 13. Order API

## 13.1 Get Orders

```http
GET /api/orders
```

### Query Parameters

```text
?status=PENDING
&machine_id=1
&page=1
&per_page=10
```

---

## 13.2 Get Order

```http
GET /api/orders/{order}
```

### Response

```json
{
    "success": true,
    "message": "Order retrieved successfully",
    "data": {
        "id": 1,
        "order_code": "ORD-20261004-0001",
        "machine_id": 1,
        "status": "PENDING",
        "total_amount": 15000,
        "items": [
            {
                "product_id": 1,
                "slot_id": 1,
                "product_name": "Nasi Goreng",
                "quantity": 1,
                "price": 15000,
                "subtotal": 15000
            }
        ]
    }
}
```

---

## 13.3 Create Order

```http
POST /api/orders
```

### Request

```json
{
    "machine_id": 1,
    "items": [
        {
            "product_id": 1,
            "slot_id": 1,
            "quantity": 1
        }
    ]
}
```

### Backend Process

```text
Receive Order
      │
      ▼
Validate Product
      │
      ▼
Validate Slot
      │
      ▼
Validate Stock
      │
      ▼
Calculate Total
      │
      ▼
Create Order
      │
      ▼
Return Order
```

Initial status:

```text
PENDING
```

---

# 14. Payment Integration API

Payment processing berada di luar core vending machine system.

Kelompok payment hanya bertanggung jawab terhadap proses pembayaran.

Setelah payment berhasil, kelompok payment memberikan konfirmasi kepada backend vending machine.

---

## 14.1 Payment Confirmation

```http
POST /api/integrations/payment/confirm
```

Endpoint ini digunakan oleh sistem payment untuk memberi tahu bahwa order telah dibayar.

### Request

```json
{
    "order_code": "ORD-20261004-0001",
    "payment_reference": "PAY-123456",
    "status": "PAID"
}
```

### Backend Process

```text
Payment System
      │
      │ Payment Success
      ▼
POST /api/integrations/payment/confirm
      │
      ▼
Find Order
      │
      ▼
Validate Order
      │
      ▼
Validate Stock
      │
      ▼
Change Order Status
      │
      ▼
READY_TO_DISPENSE
      │
      ▼
Send MQTT DISPENSE Command
```

### Response

```json
{
    "success": true,
    "message": "Payment confirmation received",
    "data": {
        "order_code": "ORD-20261004-0001",
        "status": "READY_TO_DISPENSE"
    }
}
```

### Important

Endpoint ini bukan payment processor.

Backend vending machine hanya:

- Menerima konfirmasi
- Memvalidasi order
- Memastikan order dapat diproses
- Menjalankan proses dispensing

Payment reference hanya digunakan sebagai referensi integrasi.

---

# 15. Dispense API

## 15.1 Trigger Dispense

```http
POST /api/orders/{order}/dispense
```

Endpoint ini digunakan untuk memulai proses dispensing.

### Preconditions

Order harus:

```text
READY_TO_DISPENSE
```

dan:

```text
Stock tersedia
Machine tersedia
Slot valid
```

### Backend Process

```text
POST /dispense
      │
      ▼
Validate Order
      │
      ▼
Validate Machine
      │
      ▼
Validate Slot
      │
      ▼
Validate Stock
      │
      ▼
Create Dispense
      │
      ▼
Change Order
→ DISPENSING
      │
      ▼
Publish MQTT Command
```

### MQTT Command

```json
{
    "command": "DISPENSE",
    "slot": "A01"
}
```

### Response

```json
{
    "success": true,
    "message": "Dispense command sent",
    "data": {
        "order_code": "ORD-20261004-0001",
        "status": "DISPENSING",
        "machine_id": 1,
        "slot_code": "A01"
    }
}
```

---

# 16. Dispense Result

Setelah mesin melakukan dispensing melalui MQTT, backend menerima response/status dari mesin.

### Successful Flow

```text
READY_TO_DISPENSE
        │
        ▼
    DISPENSING
        │
        ▼
       DONE
        │
        ▼
    COMPLETED
```

Backend kemudian:

```text
Decrease Stock
Update Dispense
Update Order
Store Telemetry
```

### Failed Flow

```text
READY_TO_DISPENSE
        │
        ▼
    DISPENSING
        │
        ▼
      ERROR
        │
        ▼
     FAILED
```

Backend kemudian:

```text
Store Machine Error
Update Dispense
Update Order
Notify Technician
```

---

# 17. HTTP Status Codes

| Code | Meaning |
|---|---|
| 200 | Success |
| 201 | Resource created |
| 204 | Success without response body |
| 400 | Bad request |
| 401 | Unauthenticated |
| 403 | Unauthorized |
| 404 | Resource not found |
| 409 | Conflict |
| 422 | Validation error |
| 500 | Internal server error |
| 503 | Service unavailable |

---

# 18. Validation Rules

## Product

```text
name       → required
category_id → required
price      → required, >= 0
```

## Machine

```text
machine_code           → required, unique
name                   → required
temperature_threshold  → required, >= 0
```

## Machine Slot

```text
machine_id → required
product_id → required
slot_code  → required
stock      → >= 0
capacity   → > 0
stock      → <= capacity
```

## Order

```text
machine_id → required
items      → required
quantity   → > 0
```

---

# 19. API Error Examples

## Product Not Found

```json
{
    "success": false,
    "message": "Product not found"
}
```

## Insufficient Stock

```json
{
    "success": false,
    "message": "Insufficient stock",
    "errors": {
        "slot": [
            "Requested quantity exceeds available stock."
        ]
    }
}
```

## Machine Offline

```json
{
    "success": false,
    "message": "Machine is offline"
}
```

## Invalid Order State

```json
{
    "success": false,
    "message": "Order is not ready for dispensing"
}
```

---

# 20. API Flow Summary

## Product Management

```text
Dashboard
    │
    ▼
GET /products
    │
    ├── POST /products
    ├── PUT /products/{id}
    └── DELETE /products/{id}
```

## Machine Management

```text
Dashboard
    │
    ▼
GET /machines
    │
    ├── GET /machines/{id}
    ├── POST /machines
    ├── PUT /machines/{id}
    └── DELETE /machines/{id}
```

## Stock Management

```text
Dashboard
    │
    ▼
GET /machines/{machine}/slots
    │
    └── PATCH /slots/{slot}/stock
```

## Order & Dispensing

```text
Buyer / Client
      │
      ▼
POST /orders
      │
      ▼
PENDING
      │
      ▼
Payment System
      │
      ▼
POST /integrations/payment/confirm
      │
      ▼
READY_TO_DISPENSE
      │
      ▼
POST /orders/{order}/dispense
      │
      ▼
MQTT
      │
      ▼
Machine
      │
      ▼
DISPENSING
      │
      ├────────► ERROR
      │
      ▼
DONE
      │
      ▼
COMPLETED
```

---

# 21. MQTT and REST API Boundary

REST API dan MQTT memiliki tanggung jawab berbeda.

### REST API

Digunakan untuk:

```text
Dashboard
Other Systems
Payment Integration
Business Data
Orders
Products
Machines
Monitoring
```

### MQTT

Digunakan untuk:

```text
Laravel Backend
       │
       ▼
Mosquitto
       │
       ├── Python Simulator
       └── ESP32
```

MQTT menangani:

- Machine command
- Machine status
- Telemetry
- Machine error
- Dispense response

REST API tidak digunakan sebagai jalur utama komunikasi real-time antara Laravel dan ESP32.

---

# 22. Source of Truth

Dokumen ini menjadi referensi utama untuk seluruh endpoint REST API.

Setiap perubahan terhadap:

- Endpoint
- HTTP Method
- Request
- Response
- Validation
- Authentication
- Authorization
- Integration Contract

harus diperbarui pada dokumen ini.

Implementasi Laravel API harus mengikuti specification yang telah didefinisikan.