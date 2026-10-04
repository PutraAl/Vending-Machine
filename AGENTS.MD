# Vending Machine System - Codex Instructions

## Project

Sistem Perangkat Lunak Mesin Penjual Makanan Panas Otomatis.

## Repository

This is a monorepo:

- `backend/` → Laravel + Blade + Livewire + Tailwind
- `simulator/` → Python MQTT simulator
- `firmware/` → ESP32 firmware
- `mosquitto/` → Mosquitto configuration
- `docs/` → source-of-truth project documentation

## Source of Truth

Use these documents according to the task:

- `docs/SYSTEM_OVERVIEW.md` → architecture and system boundaries
- `docs/DATABASE_SCHEMA.md` → database schema and relationships
- `docs/API_SPECIFICATION.md` → REST API contract
- `docs/MQTT_CONTRACT.md` → MQTT topics and payloads
- `docs/ROLE_PERMISSION.md` → roles and authorization
- `docs/CODING_GUIDELINE.md` → coding standards

Do not invent a new architecture when the existing documentation already defines the required behavior.

## Core Scope

The current core system includes:

- Users and roles
- Products
- Product categories
- Machines
- Machine slots
- Stock
- Orders
- Order items
- Dispensing
- Telemetry
- Machine errors
- REST API
- MQTT
- Dashboard
- Python simulator
- ESP32 firmware

The following are OUT OF SCOPE for the current core implementation:

- Payment processing
- Payment gateway
- Refund system
- AI nutritional analysis
- Product nutrition database

Payment is an external integration. Do not implement payment processing unless explicitly requested.

## Roles

Roles are stored directly in `users.role`.

Allowed values:

- `admin`
- `technician`
- `operator`

Do not create `roles` or `user_roles` tables unless explicitly requested.

## Machine State

The machine uses:

- `IDLE`
- `VALIDATING`
- `DISPENSING`
- `DONE`
- `ERROR`

Do not introduce `HEATING` as a machine state.

Temperature is telemetry / machine condition, not a state.

## MQTT

Use the documented MQTT contract.

Topics:

- `vending/machine/{machine_id}/status`
- `vending/machine/{machine_id}/telemetry`
- `vending/machine/{machine_id}/command`
- `vending/machine/{machine_id}/response`
- `vending/machine/{machine_id}/error`

Do not invent new MQTT topics without updating `docs/MQTT_CONTRACT.md`.

## Database

Follow `docs/DATABASE_SCHEMA.md`.

Use Laravel migrations for schema changes.

Do not modify the database manually as a substitute for migrations.

## Coding Rules

- Follow `docs/CODING_GUIDELINE.md`.
- Keep controllers thin.
- Put multi-step business logic in services.
- Validate input before business logic.
- Use Eloquent relationships where appropriate.
- Do not duplicate business logic.
- Keep changes focused and incremental.
- Do not add unnecessary dependencies.

## Safety

Before changing architecture, database schema, API contract, or MQTT contract:

1. Inspect the relevant documentation.
2. Explain the proposed change.
3. Wait for approval if the change affects project architecture.

Do not delete or overwrite important existing code without checking its purpose.

## Workflow

For each task:

1. Inspect relevant files.
2. State the implementation plan briefly.
3. Make the smallest reasonable change.
4. Run relevant tests or checks.
5. Review the resulting diff.
6. Summarize changed files and verification results.

Do not modify unrelated files.