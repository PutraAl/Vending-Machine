import json
import os


class MQTTPublisher:
    def __init__(self, mqtt_client):
        self.mqtt = mqtt_client

        self.machine_id = int(
            os.getenv("MQTT_MACHINE_ID", "1")
        )

    def publish_status(
        self,
        state,
        dispense_id=None,
        slot=None,
    ):
        topic = "vending/machine/status"

        payload = {
            "machine_id": self.machine_id,
            "state": state,
        }

        if dispense_id is not None:
            payload["dispense_id"] = dispense_id

        if slot is not None:
            payload["slot"] = slot

        message = json.dumps(payload)

        self.mqtt.publish(topic, message)

        print(
            f"[MQTT] STATUS → {topic} | {message}"
        )

    def publish_temperature(self, temperature):
        topic = "vending/machine/temperature"

        payload = {
            "machine_id": self.machine_id,
            "temperature": temperature,
        }

        message = json.dumps(payload)

        self.mqtt.publish(topic, message)

        print(
            f"[MQTT] TEMPERATURE → {topic} | {message}"
        )

    def publish_inventory(
        self,
        slot,
        product,
        current_qty,
    ):
        topic = "vending/machine/inventory"

        payload = {
            "machine_id": self.machine_id,
            "slot": slot,
            "product": product,
            "current_qty": current_qty,
        }

        message = json.dumps(payload)

        self.mqtt.publish(topic, message)

        print(
            f"[MQTT] INVENTORY → {topic} | {message}"
        )

    def publish_error(
        self,
        error,
        dispense_id=None,
        slot=None,
    ):
        topic = "vending/machine/error"

        payload = {
            "machine_id": self.machine_id,
            "error": error,
        }

        if dispense_id is not None:
            payload["dispense_id"] = dispense_id

        if slot is not None:
            payload["slot"] = slot

        message = json.dumps(payload)

        self.mqtt.publish(topic, message)

        print(
            f"[MQTT] ERROR → {topic} | {message}"
        )