# Coding Guideline

## 1. Overview

Dokumen ini berisi aturan dan standar pengembangan untuk project:

**Sistem Perangkat Lunak Mesin Penjual Makanan Panas Otomatis**

Guideline digunakan oleh seluruh anggota tim dan menjadi referensi bagi pengembangan:

- Laravel Backend
- Livewire
- Tailwind CSS
- REST API
- PostgreSQL
- Python Simulator
- MQTT
- ESP32 Firmware

Tujuan utama guideline:

- Menjaga struktur kode tetap konsisten.
- Memudahkan anggota tim memahami kode satu sama lain.
- Mengurangi duplikasi kode.
- Memudahkan debugging.
- Memudahkan integrasi antar komponen.
- Menjaga implementasi tetap sesuai dokumentasi project.

---

# 2. General Principles

## 2.1 Keep It Simple

Gunakan solusi yang sederhana selama sudah memenuhi kebutuhan sistem.

Hindari menambahkan:

- Library yang tidak diperlukan.
- Framework tambahan.
- Abstraksi yang berlebihan.
- Pattern yang terlalu kompleks untuk kebutuhan project.

Contoh:

```text
Simple requirement
       │
       ▼
Simple implementation
```

Jangan membuat architecture yang lebih kompleks daripada kebutuhan sebenarnya.

---

## 2.2 Separation of Responsibility

Setiap komponen harus memiliki tanggung jawab yang jelas.

```text
Controller
    │
    └── Menangani HTTP Request / Response

Service
    │
    └── Menangani Business Logic

Model
    │
    └── Menangani Data / Relationship

Livewire
    │
    └── Menangani Interactive UI

MQTT Layer
    │
    └── Menangani Communication dengan Machine
```

Jangan menempatkan seluruh logic pada controller.

---

## 2.3 Single Responsibility

Satu class atau function sebaiknya memiliki satu tanggung jawab utama.

Contoh:

```text
ProductController
    → Product-related HTTP logic

OrderService
    → Order business logic

DispenseService
    → Dispensing business logic

MQTTService
    → MQTT communication
```

Hindari membuat satu class yang menangani terlalu banyak domain sekaligus.

---

# 3. Project Structure

Struktur repository:

```text
Vending-Machine-System/
│
├── backend/
├── simulator/
├── firmware/
├── mosquitto/
├── docs/
├── docker-compose.yml
└── README.md
```

Setiap folder memiliki tanggung jawab yang berbeda.

### backend

Laravel application.

### simulator

Python MQTT simulator.

### firmware

ESP32 firmware.

### mosquitto

MQTT Broker configuration.

### docs

Project documentation.

---

# 4. Laravel Backend

## 4.1 Laravel Structure

Gunakan struktur Laravel standar.

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   │
│   ├── Models/
│   └── Services/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   └── views/
│
├── routes/
│   ├── web.php
│   └── api.php
│
└── tests/
```

Struktur tambahan dapat dibuat jika memang diperlukan.

Jangan membuat folder hanya untuk mengikuti pattern tanpa kebutuhan nyata.

---

# 5. Naming Convention

## 5.1 PHP Class

Gunakan:

```text
PascalCase
```

Contoh:

```php
ProductController
MachineController
OrderService
DispenseService
MachineTelemetry
```

---

## 5.2 Methods

Gunakan:

```text
camelCase
```

Contoh:

```php
getMachineStatus()
createOrder()
validateStock()
processDispense()
```

---

## 5.3 Variables

Gunakan:

```text
camelCase
```

Contoh:

```php
$machineId
$productId
$orderCode
$temperature
```

---

## 5.4 Database Tables

Gunakan:

```text
snake_case
```

Contoh:

```text
users
product_categories
machine_slots
order_items
machine_errors
telemetries
dispenses
```

Gunakan nama tabel berbentuk plural.

---

## 5.5 Database Columns

Gunakan:

```text
snake_case
```

Contoh:

```text
machine_id
product_id
temperature_threshold
order_code
created_at
updated_at
```

---

## 5.6 API Endpoints

Gunakan:

```text
kebab-case atau resource-based naming
```

Untuk project ini, gunakan resource-based naming seperti:

```text
/api/products
/api/machines
/api/machines/{machine}/slots
/api/orders
```

Gunakan HTTP method untuk membedakan operasi.

---

# 6. Eloquent Models

Model harus merepresentasikan entity database.

Contoh:

```text
Product
Machine
MachineSlot
Order
OrderItem
Telemetry
MachineError
Dispense
User
ProductCategory
```

Relationship harus didefinisikan pada model.

Contoh konsep:

```php
class Machine extends Model
{
    public function slots()
    {
        return $this->hasMany(MachineSlot::class);
    }
}
```

Gunakan Eloquent relationship daripada query manual berulang apabila relationship sudah tersedia.

---

# 7. Controllers

Controller digunakan untuk menangani HTTP request.

Controller sebaiknya:

```text
Receive Request
      │
      ▼
Validate Request
      │
      ▼
Call Service / Model
      │
      ▼
Return Response
```

Controller tidak seharusnya berisi business logic yang panjang.

Contoh yang dihindari:

```php
public function dispense(Request $request)
{
    // 100+ lines of business logic
}
```

Lebih baik:

```php
public function dispense(Request $request)
{
    $result = $this->dispenseService->process($request);

    return response()->json($result);
}
```

---

# 8. Form Request Validation

Gunakan Form Request untuk validation yang kompleks atau reusable.

Contoh:

```text
StoreProductRequest
UpdateProductRequest
StoreMachineRequest
CreateOrderRequest
DispenseRequest
```

Contoh:

```php
class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'machine_id' => ['required', 'exists:machines,id'],
            'items' => ['required', 'array'],
        ];
    }
}
```

Validation harus dilakukan sebelum business logic dijalankan.

---

# 9. Service Layer

Service digunakan untuk business logic yang melibatkan beberapa langkah atau entity.

Contoh service:

```text
OrderService
DispenseService
MachineService
StockService
MQTTService
TelemetryService
```

### OrderService

Bertanggung jawab terhadap:

```text
Order creation
Order validation
Order status
Order total
```

### DispenseService

Bertanggung jawab terhadap:

```text
Dispense validation
Stock validation
Create dispense
Send MQTT command
Process dispense result
```

### StockService

Bertanggung jawab terhadap:

```text
Stock update
Stock validation
Stock decrement
Stock increment
```

### MQTTService

Bertanggung jawab terhadap:

```text
Connect broker
Publish message
Subscribe topic
Receive message
```

---

# 10. Business Logic Rules

Business logic utama harus mengikuti dokumentasi:

```text
docs/SYSTEM_OVERVIEW.md
docs/DATABASE_SCHEMA.md
docs/API_SPECIFICATION.md
docs/MQTT_CONTRACT.md
docs/ROLE_PERMISSION.md
```

Jika implementasi kode berbeda dengan dokumentasi, dokumentasi harus ditinjau terlebih dahulu.

Jangan membuat behavior baru tanpa mempertimbangkan contract yang sudah ditentukan.

---

# 11. Order Rules

Order harus mengikuti flow:

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

Jika terjadi error:

```text
PENDING
     │
     └──────► FAILED

READY_TO_DISPENSE
     │
     └──────► FAILED

DISPENSING
     │
     └──────► FAILED
```

Payment processing tidak diimplementasikan pada core system.

Payment hanya diintegrasikan melalui contract yang telah ditentukan.

---

# 12. Dispense Rules

Dispense harus melakukan validasi sebelum command dikirim.

Urutan minimal:

```text
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
Validate Machine Condition
      │
      ▼
Create Dispense
      │
      ▼
Publish MQTT Command
```

Stock tidak boleh langsung dikurangi sebelum mesin memberikan konfirmasi dispensing berhasil.

Flow:

```text
DISPENSE Command
      │
      ▼
Machine
      │
      ├── FAILED
      │
      └── COMPLETED
              │
              ▼
        Decrease Stock
```

---

# 13. Machine State Rules

State machine harus konsisten antara:

- Laravel
- Python Simulator
- ESP32 Firmware

State:

```text
IDLE
VALIDATING
DISPENSING
DONE
ERROR
```

Flow:

```text
IDLE
  │
  ▼
VALIDATING
  │
  ▼
DISPENSING
  │
  ▼
DONE
  │
  ▼
IDLE
```

Error:

```text
VALIDATING ─────► ERROR
DISPENSING ─────► ERROR
ERROR ──────────► IDLE
```

`HEATING` tidak digunakan sebagai machine state.

Temperature merupakan telemetry atau condition monitoring.

---

# 14. MQTT Coding Rules

MQTT implementation harus mengikuti:

```text
docs/MQTT_CONTRACT.md
```

Topic harus menggunakan format:

```text
vending/machine/{machine_id}/status
vending/machine/{machine_id}/telemetry
vending/machine/{machine_id}/command
vending/machine/{machine_id}/response
vending/machine/{machine_id}/error
```

Payload harus berupa JSON valid.

Contoh:

```json
{
    "machine_id": "VM001",
    "state": "IDLE",
    "timestamp": "2026-10-04T10:30:00Z"
}
```

Jangan membuat topic baru tanpa memperbarui MQTT contract.

---

# 15. MQTT Error Handling

MQTT communication harus memiliki error handling.

Minimal handle:

```text
Connection Failure
Publish Failure
Subscribe Failure
Invalid JSON
Unknown Command
Timeout
Duplicate Command
```

Error harus dicatat atau ditangani sesuai konteks.

Contoh:

```python
try:
    payload = json.loads(message)
except json.JSONDecodeError:
    # Handle invalid payload
```

Jangan membiarkan invalid message menyebabkan simulator atau service berhenti.

---

# 16. Idempotency

Dispense harus mencegah proses ganda.

`order_code` digunakan sebagai identifier proses.

Contoh:

```text
ORD-20261004-0001
```

Jika command dengan `order_code` yang sama diterima lagi:

```text
Check Existing Dispense
        │
        ├── Already Completed
        │       │
        │       ▼
        │   Reject Duplicate
        │
        └── Not Processed
                │
                ▼
             Process
```

Satu order tidak boleh menghasilkan dispensing ganda.

---

# 17. Livewire Guidelines

Livewire digunakan untuk interactive dashboard.

Gunakan Livewire untuk:

```text
Tables
Filters
Search
Forms
Modal
Dashboard Widgets
Machine Monitoring
Stock Management
```

Hindari memasukkan business logic utama langsung ke component Livewire.

Gunakan:

```text
Livewire
    │
    ▼
Service
    │
    ▼
Model / Database
```

Contoh:

```text
ProductTable
     │
     ▼
ProductService
     │
     ▼
Product Model
```

---

# 18. Blade Guidelines

Blade digunakan untuk struktur dan presentation layer.

Hindari:

```php
@if ($somethingVeryComplex)
    ...
@endif
```

dengan business logic yang panjang.

Business logic harus dipindahkan ke:

```text
Controller
Service
Model
```

Blade fokus pada:

```text
Presentation
Layout
Components
Display data
```

---

# 19. Tailwind CSS Guidelines

Gunakan Tailwind CSS untuk styling dashboard.

Prioritas:

```text
Responsive
Readable
Consistent
Reusable
```

Hindari menulis CSS custom jika utility Tailwind sudah cukup.

Untuk komponen yang sering digunakan, buat reusable Blade component.

Contoh:

```text
components/
├── button.blade.php
├── badge.blade.php
├── modal.blade.php
├── table.blade.php
└── alert.blade.php
```

---

# 20. REST API Guidelines

API harus mengikuti:

```text
docs/API_SPECIFICATION.md
```

Response menggunakan format JSON yang konsisten.

Success:

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {}
}
```

Error:

```json
{
    "success": false,
    "message": "Operation failed",
    "errors": {}
}
```

Gunakan HTTP status code yang sesuai.

---

# 21. Database Guidelines

Database harus mengikuti:

```text
docs/DATABASE_SCHEMA.md
```

Gunakan migration untuk seluruh perubahan schema.

Jangan mengubah database production/development secara manual tanpa migration.

Contoh:

```text
Create Migration
      │
      ▼
Review
      │
      ▼
Run Migration
```

---

# 22. Migration Guidelines

Migration harus memiliki nama yang jelas.

Contoh:

```text
create_products_table
create_machines_table
create_machine_slots_table
create_orders_table
create_telemetries_table
```

Foreign key harus menggunakan constraint yang jelas.

Contoh:

```php
$table->foreignId('machine_id')
    ->constrained('machines');
```

---

# 23. Seeders

Gunakan seeder untuk data development dan testing.

Contoh:

```text
AdminSeeder
ProductSeeder
MachineSeeder
MachineSlotSeeder
```

Data dummy harus mudah dibedakan dari data production.

Contoh:

```text
VM001
VM002
```

---

# 24. Environment Variables

Configuration yang berbeda berdasarkan environment harus disimpan di `.env`.

Contoh:

```env
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=vending_machine
DB_USERNAME=postgres
DB_PASSWORD=

MQTT_BROKER=mosquitto
MQTT_PORT=1883
MQTT_USERNAME=
MQTT_PASSWORD=
```

Secret tidak boleh ditulis langsung di source code.

---

# 25. Git Guidelines

Gunakan Git untuk version control.

Jangan commit:

```text
.env
vendor/
node_modules/
.venv/
.pio/
logs/
secret files
```

Dokumentasi project harus ikut di-version-control:

```text
docs/
```

---

# 26. Commit Message

Gunakan commit message yang jelas.

Format:

```text
type: description
```

Contoh:

```text
feat: add product management
feat: add machine telemetry
fix: prevent duplicate dispense
fix: handle invalid mqtt payload
docs: update mqtt contract
refactor: move dispense logic to service
test: add order validation tests
```

Jenis utama:

```text
feat
fix
refactor
docs
test
chore
```

---

# 27. Branch Guidelines

Gunakan branch berdasarkan pekerjaan.

Contoh:

```text
main
│
├── feature/product-management
├── feature/machine-monitoring
├── feature/dispense-flow
├── feature/mqtt-integration
└── fix/stock-validation
```

`main` digunakan sebagai branch utama yang relatif stabil.

---

# 28. Pull Request Guidelines

Sebelum merge ke `main`:

```text
Code complete
     │
     ▼
Run Tests
     │
     ▼
Check Documentation
     │
     ▼
Review
     │
     ▼
Merge
```

Pull request harus menjelaskan:

```text
What changed
Why changed
How tested
Any known limitation
```

---

# 29. Python Simulator Guidelines

Python Simulator harus mengikuti:

```text
docs/MQTT_CONTRACT.md
```

Struktur:

```text
simulator/
├── src/
│   ├── machine/
│   ├── sensors/
│   ├── actuators/
│   ├── telemetry/
│   ├── errors/
│   ├── utils/
│   ├── mqtt/
│   └── main.py
│
├── tests/
├── requirements.txt
├── .env.example
├── .gitignore
└── README.md
```

---

# 30. Python Naming

Python mengikuti standard:

### Files

```text
snake_case.py
```

Contoh:

```text
mqtt_client.py
machine_state.py
temperature_sensor.py
```

### Functions

```python
snake_case
```

Contoh:

```python
publish_status()
handle_command()
simulate_temperature()
```

### Classes

```python
PascalCase
```

Contoh:

```python
MQTTClient
MachineState
TemperatureSensor
```

---

# 31. Python Simulator Architecture

Simulator harus dipisahkan berdasarkan responsibility.

```text
Machine
   │
   ├── State
   ├── Stock
   └── Dispensing

Sensors
   │
   └── Temperature

Actuators
   │
   └── Dispensing mechanism

Telemetry
   │
   └── Telemetry generation

MQTT
   │
   ├── Publisher
   └── Subscriber
```

Hindari memasukkan seluruh simulator ke dalam `main.py`.

`main.py` hanya bertugas melakukan orchestration.

---

# 32. ESP32 Firmware Guidelines

ESP32 firmware harus mengikuti MQTT contract yang sama dengan Python Simulator.

ESP32 bertanggung jawab terhadap:

```text
Sensor
Actuator
Machine State
MQTT
Dispensing
Telemetry
Error
```

Firmware tidak boleh memiliki business logic yang hanya dimiliki backend.

Contoh:

```text
Backend
→ Business Logic

ESP32
→ Machine Control
```

---

# 33. Backend vs Machine Responsibility

## Backend

Backend bertanggung jawab terhadap:

```text
Products
Orders
Database
Business Rules
User Authorization
Machine Management
MQTT Integration
```

## Machine

Machine bertanggung jawab terhadap:

```text
Physical Sensors
Actuators
Machine State Execution
Dispensing
Telemetry
Machine Error Detection
```

---

# 34. Logging

Gunakan logging untuk kondisi penting.

Contoh:

```text
MQTT Connected
MQTT Disconnected
Command Received
Command Published
Dispense Started
Dispense Completed
Dispense Failed
Machine Error
```

Jangan mencatat:

```text
Password
API Secret
MQTT Password
Authentication Token
```

---

# 35. Error Handling

Error harus ditangani secara eksplisit.

Jangan menggunakan:

```php
try {
    ...
} catch (\Exception $e) {
}
```

tanpa melakukan apa pun.

Minimal:

```text
Catch
  │
  ├── Log
  ├── Handle
  └── Return Appropriate Response
```

API harus mengembalikan error yang dapat dipahami client.

---

# 36. Testing Guidelines

Testing minimal mencakup:

### Backend

```text
Product CRUD
Machine CRUD
Machine Slot
Stock Validation
Order Creation
Order Validation
Dispense Flow
Role Authorization
API Validation
```

### MQTT

```text
Connect
Publish
Subscribe
Receive Command
Invalid JSON
Error Message
Duplicate Command
```

### Simulator

```text
State Transition
Dispense
Stock
Temperature
Error
MQTT Communication
```

### Integration

```text
Backend
   │
   ▼
Mosquitto
   │
   ▼
Python Simulator
```

kemudian:

```text
Backend
   │
   ▼
Mosquitto
   │
   ▼
ESP32
```

---

# 37. Documentation-First Development

Sebelum implementasi fitur baru:

```text
Requirement
    │
    ▼
Architecture
    │
    ▼
Documentation
    │
    ▼
Implementation
    │
    ▼
Testing
```

Dokumentasi yang relevan harus diperbarui apabila contract berubah.

Contoh:

### API berubah

Update:

```text
docs/API_SPECIFICATION.md
```

### MQTT berubah

Update:

```text
docs/MQTT_CONTRACT.md
```

### Database berubah

Update:

```text
docs/DATABASE_SCHEMA.md
```

### Permission berubah

Update:

```text
docs/ROLE_PERMISSION.md
```

---

# 38. Codex / AI Coding Guidelines

Codex dapat digunakan untuk membantu implementasi project.

Namun Codex harus menggunakan dokumentasi project sebagai context utama.

Dokumen source of truth:

```text
docs/SYSTEM_OVERVIEW.md
docs/DATABASE_SCHEMA.md
docs/API_SPECIFICATION.md
docs/MQTT_CONTRACT.md
docs/ROLE_PERMISSION.md
docs/CODING_GUIDELINE.md
```

Sebelum membuat implementasi:

```text
Read Relevant Documentation
          │
          ▼
Understand Existing Code
          │
          ▼
Implement Small Change
          │
          ▼
Run Test
          │
          ▼
Review Result
```

Jangan meminta AI membuat keseluruhan project sekaligus tanpa validasi bertahap.

---

# 39. AI Coding Rules

Saat menggunakan Codex:

### Rule 1 — Follow Existing Architecture

Jangan membuat architecture baru jika architecture yang ada sudah memenuhi kebutuhan.

### Rule 2 — Do Not Invent Features

Jangan menambahkan:

```text
Payment
AI Nutrition
Refund
Notification Platform
```

ke core system tanpa requirement baru.

### Rule 3 — Follow Documentation

Gunakan:

```text
DATABASE_SCHEMA.md
API_SPECIFICATION.md
MQTT_CONTRACT.md
ROLE_PERMISSION.md
```

sebagai contract.

### Rule 4 — Small Changes

Implementasi dilakukan secara bertahap.

Contoh:

```text
Product
   ↓
Machine
   ↓
Machine Slot
   ↓
Order
   ↓
Dispense
   ↓
MQTT
   ↓
Simulator
```

### Rule 5 — Test After Change

Setelah perubahan:

```text
Code
  ↓
Test
  ↓
Fix
  ↓
Review
```

---

# 40. Code Review Checklist

Sebelum code dianggap selesai:

```text
[ ] Mengikuti architecture
[ ] Mengikuti naming convention
[ ] Tidak ada duplicate logic
[ ] Validation tersedia
[ ] Error handling tersedia
[ ] Authorization sesuai role
[ ] Tidak ada secret
[ ] Test berjalan
[ ] Dokumentasi sesuai
```

---

# 41. Definition of Done

Sebuah fitur dianggap selesai apabila:

```text
Requirement selesai
       │
       ▼
Code selesai
       │
       ▼
Validation selesai
       │
       ▼
Testing selesai
       │
       ▼
Documentation diperbarui
       │
       ▼
Ready for Integration
```

---

# 42. Source of Truth

Dokumen ini menjadi referensi utama untuk standar coding dan development workflow.

Implementation harus tetap konsisten dengan:

```text
docs/SYSTEM_OVERVIEW.md
docs/DATABASE_SCHEMA.md
docs/API_SPECIFICATION.md
docs/MQTT_CONTRACT.md
docs/ROLE_PERMISSION.md
docs/CODING_GUIDELINE.md
```

Jika terdapat perubahan architecture atau requirement, perubahan harus didokumentasikan terlebih dahulu sebelum implementasi besar dilakukan.