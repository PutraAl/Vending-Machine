# MQTT Contract

## 1. Overview

MQTT digunakan sebagai communication protocol antara Laravel Backend dengan vending machine.

Komunikasi menggunakan **Mosquitto sebagai MQTT Broker**.

MQTT digunakan untuk komunikasi machine-level seperti:

- Machine command
- Machine status
- Machine telemetry
- Machine response
- Machine error

MQTT tidak digunakan untuk:

- User authentication
- Product CRUD
- Order CRUD
- Dashboard communication
- Payment processing

REST API digunakan untuk kebutuhan business logic dan data management.

---

# 2. MQTT Architecture

```text
                    Laravel Backend
                          │
                          │ MQTT Publish / Subscribe
                          ▼
                  ┌─────────────────┐
                  │ Mosquitto Broker│
                  └────────┬────────┘
                           │
                ┌──────────┴──────────┐
                │                     │
                ▼                     ▼
        Python Simulator          ESP32 Firmware
```

Python Simulator dan ESP32 menggunakan MQTT contract yang sama.

Artinya:

```text
Python Simulator
       │
       │ mengikuti contract yang sama
       ▼
ESP32 Firmware
```

Hal ini memungkinkan simulator digunakan sebagai pengganti hardware selama tahap development dan testing.

---

# 3. Broker Configuration

Default development configuration:

```text
Host:
localhost

Port:
1883

Protocol:
MQTT

Version:
MQTT 3.1.1
```

Configuration dapat disesuaikan berdasarkan environment.

Contoh `.env`:

```env
MQTT_BROKER=localhost
MQTT_PORT=1883
MQTT_USERNAME=
MQTT_PASSWORD=
```

Credential tidak boleh ditulis langsung di source code.

---

# 4. Topic Structure

Setiap topic menggunakan `machine_id` atau `machine_code` sebagai identifier mesin.

Format utama:

```text
vending/machine/{machine_id}/status
vending/machine/{machine_id}/telemetry
vending/machine/{machine_id}/command
vending/machine/{machine_id}/response
vending/machine/{machine_id}/error
```

Contoh:

```text
vending/machine/VM001/status
vending/machine/VM001/telemetry
vending/machine/VM001/command
vending/machine/VM001/response
vending/machine/VM001/error
```

`VM001` merupakan `machine_code` yang tersimpan pada database.

---

# 5. Topic Responsibility

| Topic | Publisher | Subscriber | Purpose |
|---|---|---|---|
| `/status` | Machine | Backend | Machine status |
| `/telemetry` | Machine | Backend | Sensor and machine telemetry |
| `/command` | Backend | Machine | Command to machine |
| `/response` | Machine | Backend | Response to command |
| `/error` | Machine | Backend | Machine error |

---

# 6. Communication Direction

## Backend → Machine

```text
Laravel Backend
      │
      │ Publish
      ▼
vending/machine/{machine_id}/command
      │
      ▼
Machine
```

Digunakan untuk mengirim command seperti:

```text
DISPENSE
```

---

## Machine → Backend

```text
Machine
   │
   ├── Publish Status
   ├── Publish Telemetry
   ├── Publish Response
   └── Publish Error
          │
          ▼
       Mosquitto
          │
          ▼
   Laravel Backend
```

---

# 7. Machine Status

## Topic

```text
vending/machine/{machine_id}/status
```

Publisher:

```text
Machine
```

Subscriber:

```text
Laravel Backend
```

### Payload

```json
{
    "machine_id": "VM001",
    "status": "ONLINE",
    "state": "IDLE",
    "timestamp": "2026-10-04T10:30:00Z"
}
```

### Status

Connection status:

```text
ONLINE
OFFLINE
```

### State

Machine state:

```text
IDLE
VALIDATING
DISPENSING
DONE
ERROR
```

---

# 8. Machine Telemetry

## Topic

```text
vending/machine/{machine_id}/telemetry
```

Publisher:

```text
Machine
```

Subscriber:

```text
Laravel Backend
```

### Payload

```json
{
    "machine_id": "VM001",
    "temperature": 72.5,
    "state": "IDLE",
    "door_status": "CLOSED",
    "timestamp": "2026-10-04T10:30:00Z"
}
```

### Fields

| Field | Type | Required | Description |
|---|---|---:|---|
| machine_id | string | Yes | Machine identifier |
| temperature | number | Yes | Machine temperature |
| state | string | Yes | Current machine state |
| door_status | string | Yes | Door condition |
| timestamp | datetime | Yes | Telemetry timestamp |

### Door Status

```text
OPEN
CLOSED
```

---

# 9. Machine Command

## Topic

```text
vending/machine/{machine_id}/command
```

Publisher:

```text
Laravel Backend
```

Subscriber:

```text
Machine
```

Command digunakan untuk memberikan instruksi kepada mesin.

---

# 10. DISPENSE Command

Command utama pada sistem adalah `DISPENSE`.

### Payload

```json
{
    "command": "DISPENSE",
    "order_code": "ORD-20261004-0001",
    "slot": "A01",
    "quantity": 1,
    "timestamp": "2026-10-04T10:30:00Z"
}
```

### Fields

| Field | Type | Required | Description |
|---|---|---:|---|
| command | string | Yes | Command name |
| order_code | string | Yes | Order identifier |
| slot | string | Yes | Slot to dispense |
| quantity | integer | Yes | Quantity to dispense |
| timestamp | datetime | Yes | Command timestamp |

### Example

```text
Order:
ORD-20261004-0001

Machine:
VM001

Slot:
A01

Quantity:
1
```

Backend publish:

```text
vending/machine/VM001/command
```

Payload:

```json
{
    "command": "DISPENSE",
    "order_code": "ORD-20261004-0001",
    "slot": "A01",
    "quantity": 1,
    "timestamp": "2026-10-04T10:30:00Z"
}
```

---

# 11. Command Validation

Machine tidak langsung menjalankan command tanpa validasi.

Alur:

```text
Receive Command
      │
      ▼
Validate JSON
      │
      ▼
Validate Command
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
VALIDATING
      │
      ▼
DISPENSING
```

Jika validasi gagal:

```text
VALIDATING
      │
      ▼
ERROR
```

---

# 12. Machine Response

## Topic

```text
vending/machine/{machine_id}/response
```

Publisher:

```text
Machine
```

Subscriber:

```text
Laravel Backend
```

Response digunakan untuk memberikan hasil pemrosesan command.

---

## 12.1 Command Accepted

```json
{
    "command": "DISPENSE",
    "order_code": "ORD-20261004-0001",
    "status": "ACCEPTED",
    "slot": "A01",
    "timestamp": "2026-10-04T10:30:01Z"
}
```

---

## 12.2 Dispense Completed

```json
{
    "command": "DISPENSE",
    "order_code": "ORD-20261004-0001",
    "status": "COMPLETED",
    "slot": "A01",
    "quantity": 1,
    "timestamp": "2026-10-04T10:30:08Z"
}
```

---

## 12.3 Dispense Failed

```json
{
    "command": "DISPENSE",
    "order_code": "ORD-20261004-0001",
    "status": "FAILED",
    "slot": "A01",
    "error_code": "DISPENSER_JAM",
    "timestamp": "2026-10-04T10:30:08Z"
}
```

---

# 13. Machine Error

## Topic

```text
vending/machine/{machine_id}/error
```

Publisher:

```text
Machine
```

Subscriber:

```text
Laravel Backend
```

### Payload

```json
{
    "machine_id": "VM001",
    "error_code": "TEMPERATURE_HIGH",
    "message": "Machine temperature exceeded threshold",
    "severity": "WARNING",
    "state": "ERROR",
    "timestamp": "2026-10-04T10:31:00Z"
}
```

### Error Codes

Initial error codes:

```text
TEMPERATURE_HIGH
SLOT_EMPTY
DISPENSER_JAM
DOOR_OPEN
MQTT_ERROR
INVALID_COMMAND
UNKNOWN_ERROR
```

Error code dapat dikembangkan sesuai kebutuhan hardware.

---

# 14. Error Severity

Severity yang digunakan:

```text
INFO
WARNING
CRITICAL
```

### INFO

Informasi normal yang tidak membutuhkan tindakan segera.

### WARNING

Kondisi yang perlu diperhatikan.

Contoh:

```text
TEMPERATURE_HIGH
```

### CRITICAL

Kondisi yang dapat menyebabkan mesin tidak dapat beroperasi.

Contoh:

```text
DISPENSER_JAM
```

---

# 15. State Machine

Machine menggunakan state:

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

Error transition:

```text
VALIDATING ───────► ERROR
DISPENSING ───────► ERROR
ERROR ────────────► IDLE
```

`HEATING` tidak termasuk state machine karena machine berada dalam kondisi panas secara terus-menerus.

Temperature hanya menjadi telemetry dan condition monitoring.

---

# 16. DISPENSE Communication Flow

Alur lengkap proses dispensing:

```text
Payment System
      │
      │ Payment Success
      ▼
Laravel Backend
      │
      │ Validate Order
      │ Validate Stock
      │ Validate Machine
      ▼
READY_TO_DISPENSE
      │
      │ MQTT Publish
      ▼
Mosquitto
      │
      ▼
Machine
      │
      ▼
VALIDATING
      │
      ▼
DISPENSING
      │
      ├──────────────► ERROR
      │                  │
      │                  ▼
      │              Publish Error
      │
      ▼
DONE
      │
      ▼
Publish Response
      │
      ▼
Laravel Backend
      │
      ▼
COMPLETED
```

---

# 17. Telemetry Flow

```text
Sensor / Machine
       │
       │ Publish
       ▼
Mosquitto
       │
       ▼
Laravel Backend
       │
       ├── Save Telemetry
       ├── Update Machine Status
       ├── Check Temperature
       │
       └── Notify Technician
```

Temperature threshold:

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

---

# 18. Stock Synchronization

Stock pada database dan machine harus tetap konsisten.

### Before Dispense

```text
Database Stock
      │
      ▼
Validate Stock
      │
      ▼
Send DISPENSE
```

### After Successful Dispense

```text
Machine
   │
   │ COMPLETED
   ▼
Laravel Backend
   │
   ▼
Decrease Database Stock
```

Contoh:

```text
Before:
Stock = 5

Dispense:
Quantity = 1

After:
Stock = 4
```

Database stock hanya dikurangi setelah machine memberikan response bahwa dispensing berhasil.

---

# 19. MQTT Quality of Service

QoS yang digunakan dapat dibedakan berdasarkan jenis message.

### Command

```text
QoS = 1
```

Command harus memiliki kemungkinan diterima kembali apabila proses publish mengalami masalah.

### Response

```text
QoS = 1
```

Response penting untuk mengetahui hasil command.

### Error

```text
QoS = 1
```

Error harus memiliki tingkat reliability yang baik.

### Telemetry

```text
QoS = 0
```

Telemetry dapat dikirim secara berkala dan tidak semua message harus diterima.

---

# 20. Retained Messages

Untuk status machine, retained message dapat digunakan agar subscriber baru dapat langsung menerima status terakhir machine.

Contoh:

```text
vending/machine/VM001/status
```

dapat menggunakan:

```text
retain = true
```

Telemetry tidak perlu menggunakan retained message.

---

# 21. MQTT Client Identity

Setiap machine atau simulator harus memiliki client identifier yang unik.

Contoh:

```text
vending-machine-VM001
vending-simulator-VM001
```

Client ID tidak boleh sama pada waktu yang sama.

---

# 22. Topic Rules

Topic harus mengikuti format:

```text
vending/machine/{machine_id}/{channel}
```

Channel yang diperbolehkan:

```text
status
telemetry
command
response
error
```

Contoh valid:

```text
vending/machine/VM001/status
vending/machine/VM001/telemetry
vending/machine/VM001/command
vending/machine/VM001/response
vending/machine/VM001/error
```

Contoh tidak valid:

```text
machine/status
vending/status
VM001/command
```

---

# 23. Payload Rules

Semua payload MQTT menggunakan JSON.

Rules:

```text
1. JSON harus valid.
2. Field name menggunakan snake_case.
3. Enum/state menggunakan uppercase.
4. Timestamp menggunakan ISO 8601.
5. Machine identifier wajib tersedia pada payload jika diperlukan untuk tracing.
6. Payload tidak boleh mengandung password atau secret.
```

Contoh:

```json
{
    "machine_id": "VM001",
    "state": "IDLE",
    "timestamp": "2026-10-04T10:30:00Z"
}
```

---

# 24. Invalid JSON Handling

Jika machine menerima payload yang bukan JSON valid:

```text
MQTT Message
      │
      ▼
Parse JSON
      │
      ├── Valid
      │     │
      │     ▼
      │   Process
      │
      └── Invalid
            │
            ▼
      Reject Command
            │
            ▼
        Publish Error
```

Error:

```json
{
    "machine_id": "VM001",
    "error_code": "INVALID_COMMAND",
    "message": "Invalid JSON payload",
    "severity": "WARNING",
    "timestamp": "2026-10-04T10:30:00Z"
}
```

---

# 25. Duplicate Command Handling

Command dispensing dapat diterima lebih dari satu kali karena QoS atau network retry.

Machine harus menghindari dispensing ganda untuk order yang sama.

`order_code` digunakan sebagai identifier proses.

Contoh:

```text
Order:
ORD-20261004-0001
```

Jika command yang sama diterima dua kali:

```text
First Command
      │
      ▼
Process
      │
      ▼
COMPLETED

Second Command
      │
      ▼
Detect Existing Order
      │
      ▼
Reject Duplicate
```

Machine tidak boleh melakukan dispensing dua kali untuk order yang sama.

---

# 26. Connection Monitoring

Backend perlu mengetahui apakah machine masih aktif.

Machine dapat mengirim status secara berkala.

Contoh:

```text
Every N seconds
      │
      ▼
Publish STATUS
      │
      ▼
Backend updates machine activity
```

Jika tidak ada message dalam interval tertentu:

```text
No MQTT Message
      │
      ▼
Connection Timeout
      │
      ▼
Machine OFFLINE
```

Nilai timeout ditentukan pada implementasi berdasarkan kebutuhan sistem.

---

# 27. Python Simulator Contract

Python Simulator harus mengikuti MQTT contract yang sama dengan ESP32.

Simulator harus dapat:

### Subscribe

```text
vending/machine/{machine_id}/command
```

### Publish

```text
vending/machine/{machine_id}/status
vending/machine/{machine_id}/telemetry
vending/machine/{machine_id}/response
vending/machine/{machine_id}/error
```

Contoh:

```text
Python Simulator
      │
      ├── Subscribe Command
      │
      ├── Simulate State Machine
      │
      ├── Simulate Dispense
      │
      ├── Publish Status
      │
      ├── Publish Telemetry
      │
      └── Publish Response / Error
```

---

# 28. ESP32 Contract

ESP32 mengikuti contract yang sama dengan Python Simulator.

ESP32 harus dapat:

```text
Subscribe:
vending/machine/{machine_id}/command
```

dan publish:

```text
vending/machine/{machine_id}/status
vending/machine/{machine_id}/telemetry
vending/machine/{machine_id}/response
vending/machine/{machine_id}/error
```

Perbedaan antara simulator dan ESP32 hanya berada pada implementasi hardware.

```text
                MQTT CONTRACT
                     │
          ┌──────────┴──────────┐
          │                     │
       Simulator              ESP32
          │                     │
      Software               Hardware
     Simulation              Sensors
     Actuators               Actuators
```

---

# 29. Security Considerations

Development environment dapat menggunakan:

```text
localhost:1883
```

Production environment harus menggunakan authentication dan koneksi yang lebih aman.

Credential MQTT harus disimpan sebagai environment variable.

Contoh:

```env
MQTT_USERNAME=machine_user
MQTT_PASSWORD=********
```

Credential tidak boleh:

- Ditulis langsung pada source code
- Di-commit ke Git
- Dicantumkan dalam dokumentasi publik

---

# 30. Testing Scenarios

MQTT communication harus diuji dengan skenario berikut.

## Scenario 1 — Connect

```text
Machine
   │
   ▼
Connect Broker
   │
   ▼
Publish ONLINE
```

## Scenario 2 — Dispense

```text
Backend
   │
   ▼
DISPENSE Command
   │
   ▼
Machine
   │
   ▼
DISPENSING
   │
   ▼
DONE
   │
   ▼
COMPLETED Response
```

## Scenario 3 — Empty Slot

```text
DISPENSE
   │
   ▼
SLOT_EMPTY
   │
   ▼
ERROR
   │
   ▼
Backend
```

## Scenario 4 — High Temperature

```text
Telemetry
   │
   ▼
Temperature > Threshold
   │
   ▼
WARNING
   │
   ▼
Technician Notification
```

## Scenario 5 — Invalid JSON

```text
Invalid Message
      │
      ▼
JSON Parse Failed
      │
      ▼
INVALID_COMMAND
```

## Scenario 6 — Duplicate Command

```text
Same order_code
      │
      ▼
Already Processed
      │
      ▼
Reject Duplicate
```

---

# 31. Source of Truth

Dokumen ini menjadi referensi utama untuk seluruh komunikasi MQTT pada sistem.

Perubahan terhadap:

- Topic
- Payload
- Command
- Response
- Error
- QoS
- State
- Communication Flow

harus diperbarui pada dokumen ini.

Python Simulator dan ESP32 Firmware harus mengikuti contract ini agar keduanya dapat menggunakan backend dan MQTT Broker dengan protokol yang sama.