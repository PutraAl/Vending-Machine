
import json
import os
import sqlite3
import time

from datetime import datetime, timezone
from pathlib import Path

from mqtt.publisher import MQTTPublisher

class MQTTSubscriber:
    def __init__(self, mqtt_client):
        self.mqtt = mqtt_client
        self.publisher = MQTTPublisher(mqtt_client)
        # Machine identity
        self.machine_id = int(
            os.getenv("MQTT_MACHINE_ID", "1")
        )

        # Laravel commands include machine identity and expiry; require both
        # by default while keeping environment switches for controlled legacy use.
        self.require_machine_id = (
            os.getenv("MQTT_REQUIRE_MACHINE_ID", "true").lower()
            == "true"
        )

        self.require_command_expiry = (
            os.getenv("MQTT_REQUIRE_COMMAND_EXPIRY", "true").lower()
            == "true"
        )

        self.max_command_ttl_seconds = int(
            os.getenv("MQTT_MAX_COMMAND_TTL_SECONDS", "60")
        )

        # Test-only failure simulation
        self.allow_test_failures = (
            os.getenv("SIMULATOR_ALLOW_TEST_FAILURES", "false").lower()
            == "true"
        )

        if self.max_command_ttl_seconds <= 0:
            raise ValueError("MQTT_MAX_COMMAND_TTL_SECONDS must be positive.")

        # Persistent command history.
        # Stored locally so processed dispense IDs survive restarts.
        self.command_db_path = (
            Path(__file__).resolve().parents[2]
            / "data"
            / "dispense_commands.sqlite3"
        )
        self.command_db_path.parent.mkdir(
            parents=True,
            exist_ok=True,
        )

        self._initialize_command_store()
        self.mqtt.client.on_message = self._on_message

    def _connect_store(self):
        connection = sqlite3.connect(
            self.command_db_path,
            timeout=10,
        )
        connection.row_factory = sqlite3.Row
        return connection

    def _initialize_command_store(self):
        connection = self._connect_store()

        try:
            with connection:
                connection.execute(
                    """
                    CREATE TABLE IF NOT EXISTS dispense_commands (
                        dispense_id INTEGER PRIMARY KEY,
                        slot TEXT NOT NULL,
                        quantity INTEGER NOT NULL,
                        simulate_error INTEGER NOT NULL,
                        status TEXT NOT NULL
                            CHECK (
                                status IN (
                                    'PROCESSING',
                                    'DONE',
                                    'FAILED'
                                )
                            ),
                        error TEXT,
                        updated_at TEXT NOT NULL
                            DEFAULT CURRENT_TIMESTAMP
                    )
                    """
                )
        finally:
            connection.close()

    def _claim_command(
        self,
        dispense_id,
        slot,
        quantity,
        simulate_error,
    ):
        """
        Atomically register the dispense ID before processing it.

        A previously registered ID must never start a second
        dispensing operation automatically.
        """
        connection = self._connect_store()

        try:
            with connection:
                cursor = connection.execute(
                    """
                    INSERT INTO dispense_commands (
                        dispense_id,
                        slot,
                        quantity,
                        simulate_error,
                        status,
                        error
                    )
                    VALUES (?, ?, ?, ?, 'PROCESSING', NULL)
                    ON CONFLICT(dispense_id) DO NOTHING
                    """,
                    (
                        dispense_id,
                        slot,
                        quantity,
                        int(simulate_error),
                    ),
                )

                existing = connection.execute(
                    """
                    SELECT *
                    FROM dispense_commands
                    WHERE dispense_id = ?
                    """,
                    (dispense_id,),
                ).fetchone()

                if existing is None:
                    raise RuntimeError(
                        "Could not register dispense command."
                    )

                is_new = cursor.rowcount == 1

                same_command = (
                    existing["slot"] == slot
                    and existing["quantity"] == quantity
                    and existing["simulate_error"]
                    == int(simulate_error)
                )

                return {
                    "is_new": is_new,
                    "same_command": same_command,
                    "status": existing["status"],
                    "error": existing["error"],
                }
        finally:
            connection.close()

    def _update_command(
        self,
        dispense_id,
        status,
        error=None,
    ):
        connection = self._connect_store()

        try:
            with connection:
                connection.execute(
                    """
                    UPDATE dispense_commands
                    SET status = ?,
                        error = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE dispense_id = ?
                    """,
                    (status, error, dispense_id),
                )
        finally:
            connection.close()

    def subscribe_commands(self):
        topic = "vending/machine/command"

        success = self.mqtt.subscribe(
            topic,
            qos=1
        )

        if not success:
            raise RuntimeError(
                f"Failed to subscribe to {topic}"
            )

        print(f"[MQTT] Subscribed -> {topic}")
        print(
            "[SIMULATOR] Persistent dispense "
            "deduplication is enabled."
        )

    def _on_message(self, client, userdata, message):
                # Security: reject retained commands.
        if message.retain:
            print(
                "[SECURITY] Retained command rejected."
            )

            self.publisher.publish_error(
                "RETAINED_COMMAND_REJECTED"
            )
            return

        # Dispense commands must use QoS 1 or higher.
        if message.qos < 1:
            print(
                "[SECURITY] Command rejected: "
                "QoS below 1."
            )

            self.publisher.publish_error(
                "COMMAND_QOS_TOO_LOW"
            )
            return
        try:
            raw_message = message.payload.decode("utf-8")
            payload = json.loads(raw_message)

        except (UnicodeDecodeError, json.JSONDecodeError) as error:
            print(f"[MQTT] Invalid JSON received: {error}")
            print(f"[MQTT] Raw payload: {message.payload!r}")

            self.publisher.publish_error("INVALID_COMMAND_JSON")
            return

        if not isinstance(payload, dict):
            print("[SIMULATOR] Command payload must be a JSON object.")
            self.publisher.publish_error("INVALID_DISPENSE_COMMAND")
            return

                # ==========================================
        # MACHINE ID VALIDATION
        # ==========================================

        raw_machine_id = payload.get("machine_id")

        if raw_machine_id is None:

            if self.require_machine_id:
                print(
                    "[SECURITY] Command rejected: "
                    "machine_id is required."
                )

                self.publisher.publish_error(
                    "MISSING_MACHINE_ID"
                )
                return

            print(
                "[SECURITY] Warning: command has no machine_id. "
                "Compatibility mode is enabled."
            )

        else:

            try:
                if isinstance(raw_machine_id, bool):
                    raise ValueError("Boolean machine_id is invalid")

                if isinstance(raw_machine_id, int):
                    command_machine_id = raw_machine_id

                elif (
                    isinstance(raw_machine_id, str)
                    and raw_machine_id.strip().isdigit()
                ):
                    command_machine_id = int(raw_machine_id.strip())

                else:
                    raise ValueError("Invalid machine_id format")

            except ValueError:
                self.publisher.publish_error(
                    "INVALID_MACHINE_ID"
                )
                return

            if command_machine_id != self.machine_id:

                print(
                    f"[SECURITY] Command rejected: "
                    f"target machine={command_machine_id}, "
                    f"current machine={self.machine_id}"
                )

                self.publisher.publish_error(
                    "WRONG_MACHINE_ID"
                )
                return
        print(f"\n[MQTT] Raw message: {raw_message}")
        print(f"[MQTT] Command received: {payload}")

        # ==========================================
        # COMMAND EXPIRY VALIDATION
        # ==========================================

        raw_expires_at = payload.get("expires_at")

        if raw_expires_at is None:

            if self.require_command_expiry:
                print(
                    "[SECURITY] Command rejected: "
                    "expires_at is required."
                )

                self.publisher.publish_error(
                    "MISSING_COMMAND_EXPIRY"
                )
                return

            print(
                "[SECURITY] Warning: command has no expires_at. "
                "Compatibility mode is enabled."
            )

        else:

            try:
                if not isinstance(raw_expires_at, str):
                    raise ValueError("expires_at must be a string")

                expiry_text = raw_expires_at.strip()

                if expiry_text.endswith("Z"):
                    expiry_text = (
                        expiry_text[:-1] + "+00:00"
                    )

                expiry = datetime.fromisoformat(expiry_text)

                if expiry.tzinfo is None:
                    raise ValueError(
                        "expires_at must include timezone."
                    )

                now = datetime.now(timezone.utc)

                remaining_seconds = (
                    expiry.astimezone(timezone.utc) - now
                ).total_seconds()

                if remaining_seconds <= 0:
                    print(
                        "[SECURITY] Expired command rejected."
                    )

                    self.publisher.publish_error(
                        "COMMAND_EXPIRED"
                    )
                    return

                if remaining_seconds > self.max_command_ttl_seconds:
                    print(
                        "[SECURITY] Command expiry exceeds "
                        "maximum permitted TTL."
                    )

                    self.publisher.publish_error(
                        "COMMAND_TTL_TOO_LONG"
                    )
                    return

            except (ValueError, TypeError, OverflowError) as error:

                print(
                    f"[SECURITY] Invalid command expiry: {error}"
                )

                self.publisher.publish_error(
                    "INVALID_COMMAND_EXPIRY"
                )
                return
        command = payload.get("command")

        if command != "DISPENSE":
            print(f"[SIMULATOR] Unknown command: {command}")
            self.publisher.publish_error(
                f"UNKNOWN_COMMAND:{command}"
            )
            return

        raw_dispense_id = payload.get("dispense_id")
        slot = payload.get("slot")
        raw_quantity = payload.get("quantity", 1)
        simulate_error = payload.get("simulate_error", False)

        # Validate dispense ID.
        if isinstance(raw_dispense_id, bool):
            dispense_id = None
        else:
            try:
                dispense_id = int(raw_dispense_id)
            except (TypeError, ValueError):
                dispense_id = None

        if (
            dispense_id is None
            or dispense_id <= 0
            or not isinstance(slot, str)
            or not slot.strip()
        ):
            print(
                "[SIMULATOR] Missing or invalid dispense_id or slot."
            )
            self.publisher.publish_error(
                "INVALID_DISPENSE_COMMAND"
            )
            return

        # Validate quantity without silently truncating decimals.
        try:
            if isinstance(raw_quantity, bool):
                raise ValueError("quantity cannot be boolean")

            if isinstance(raw_quantity, float) and not raw_quantity.is_integer():
                raise ValueError("quantity must be an integer")

            if isinstance(raw_quantity, str) and not raw_quantity.strip().isdigit():
                raise ValueError("quantity must be a positive integer")

            quantity = int(raw_quantity)

            if quantity < 1:
                raise ValueError("quantity must be greater than zero")
        except (TypeError, ValueError) as error:
            print(f"[SIMULATOR] Invalid quantity: {error}")
            self.publisher.publish_error(
                "INVALID_DISPENSE_COMMAND",
                dispense_id=dispense_id,
                slot=slot,
            )
            return

        if not isinstance(simulate_error, bool):
            print("[SIMULATOR] simulate_error must be boolean.")
            self.publisher.publish_error(
                "INVALID_DISPENSE_COMMAND",
                dispense_id=dispense_id,
                slot=slot,
            )
            return
        if simulate_error and not self.allow_test_failures:
            print(
                "[SECURITY] Test failure simulation is disabled."
            )

            self.publisher.publish_error(
                "TEST_FAILURE_SIMULATION_DISABLED",
                dispense_id=dispense_id,
                slot=slot,
            )
            return
        try:
            claim = self._claim_command(
                dispense_id=dispense_id,
                slot=slot,
                quantity=quantity,
                simulate_error=simulate_error,
            )
        except Exception as error:
            # Fail closed: do not operate if the persistent
            # deduplication record cannot be checked.
            print(
                "[SIMULATOR] Cannot verify command history. "
                f"Command rejected: {error}"
            )
            return

        if not claim["is_new"]:
            if not claim["same_command"]:
                print(
                    f"[SIMULATOR] Conflicting duplicate command "
                    f"for dispense {dispense_id}; rejected."
                )
                return

            previous_status = claim["status"]

            print(
                f"[SIMULATOR] Duplicate dispense {dispense_id}; "
                f"previous status={previous_status}."
            )

            if previous_status == "DONE":
                # Re-send the previous result, not the operation.
                self.publisher.publish_status(
                    state="DONE",
                    dispense_id=dispense_id,
                    slot=slot,
                )
                print(
                    f"[SIMULATOR] Previous DONE result "
                    f"re-published for {dispense_id}."
                )

            elif previous_status == "FAILED":
                self.publisher.publish_error(
                    claim["error"] or "DISPENSE_FAILED",
                    dispense_id=dispense_id,
                    slot=slot,
                )
                print(
                    f"[SIMULATOR] Previous failure result "
                    f"re-published for {dispense_id}."
                )

            else:
                # PROCESSING after a restart is ambiguous.
                # Do not retry automatically: the physical action
                # may or may not have happened before the restart.
                print(
                    f"[SIMULATOR] Dispense {dispense_id} has an "
                    "unfinished record. Automatic replay refused; "
                    "reconciliation is required."
                )

            return

        try:
            result = self._process_dispense(
                dispense_id=dispense_id,
                slot=slot,
                quantity=quantity,
                simulate_error=simulate_error,
            )

            if result == "FAILED":
                self._update_command(
                    dispense_id,
                    "FAILED",
                    "DISPENSE_FAILED",
                )
            else:
                self._update_command(
                    dispense_id,
                    "DONE",
                )

     
        except Exception as error:
            print(
                f"[SIMULATOR] Dispense error: {error}"
            )

            print(
                f"[SIMULATOR] Outcome for dispense {dispense_id} "
                "is uncertain. Keeping PROCESSING for reconciliation."
            )

            # Do not mark as FAILED automatically.
            # The physical outcome may be uncertain.
            # Keep the persistent record as PROCESSING.
            try:
                self.publisher.publish_error(
                    "DISPENSE_OUTCOME_UNKNOWN",
                    dispense_id=dispense_id,
                    slot=slot,
                )
            except Exception as publish_error:
                print(
                    "[SIMULATOR] Could not publish uncertain "
                    f"outcome: {publish_error}"
                )

    def _process_dispense(
        self,
        dispense_id,
        slot,
        quantity,
        simulate_error=False,
    ): 
        print(
            f"[SIMULATOR] Starting dispense "
            f"dispense_id={dispense_id}, "
            f"slot={slot}, "
            f"quantity={quantity}"
        )

        # VALIDATING
        self.publisher.publish_status(
            state="VALIDATING",
            dispense_id=dispense_id,
            slot=slot,
        )

        time.sleep(1)

        # Simulate a machine failure.
        if simulate_error:
            print(
                f"[SIMULATOR] Simulating ERROR "
                f"for dispense {dispense_id}"
            )

            self.publisher.publish_error(
                "DISPENSE_FAILED",
                dispense_id=dispense_id,
                slot=slot,
            )

            return "FAILED"

        # DISPENSING
        self.publisher.publish_status(
            state="DISPENSING",
            dispense_id=dispense_id,
            slot=slot,
        )

        time.sleep(2)

        # DONE
        self.publisher.publish_status(
            state="DONE",
            dispense_id=dispense_id,
            slot=slot,
        )

        print(
            f"[SIMULATOR] Dispense completed "
            f"for {slot}"
        )

        return "DONE"
