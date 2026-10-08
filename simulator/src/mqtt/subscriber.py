import json
import time

from mqtt.publisher import MQTTPublisher


class MQTTSubscriber:
    def __init__(self, mqtt_client):
        self.mqtt = mqtt_client
        self.publisher = MQTTPublisher(mqtt_client)

        self.mqtt.client.on_message = self._on_message

    def subscribe_commands(self):
        topic = "vending/machine/command"

        self.mqtt.client.subscribe(topic)

        print(f"[MQTT] Subscribed → {topic}")

    def _on_message(self, client, userdata, message):
        raw_message = message.payload.decode("utf-8")

        print(f"\n[MQTT] Raw message: {raw_message}")

        try:
            payload = json.loads(raw_message)

        except json.JSONDecodeError as error:
            print(
                f"[MQTT] Invalid JSON received: {error}"
            )

            self.publisher.publish_error(
                "INVALID_COMMAND_JSON"
            )

            return

        print(
            f"[MQTT] Command received: {payload}"
        )

        command = payload.get("command")
        dispense_id = payload.get("dispense_id")
        slot = payload.get("slot")
        quantity = payload.get("quantity", 1)
        simulate_error = payload.get(
            "simulate_error",
            False
        )

        if command != "DISPENSE":
            print(
                f"[SIMULATOR] Unknown command: {command}"
            )

            self.publisher.publish_error(
                f"UNKNOWN_COMMAND:{command}"
            )

            return

        if not dispense_id or not slot:
            print(
                "[SIMULATOR] Missing dispense_id or slot."
            )

            self.publisher.publish_error(
                "INVALID_DISPENSE_COMMAND"
            )

            return

        try:
            self._process_dispense(
                dispense_id=dispense_id,
                slot=slot,
                quantity=int(quantity),
                simulate_error=bool(simulate_error),
            )

        except Exception as error:
            print(
                f"[SIMULATOR] Dispense failed: {error}"
            )

            self.publisher.publish_error(
                "DISPENSE_FAILED",
                dispense_id=dispense_id,
                slot=slot,
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

        # Simulate machine failure
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

            return

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