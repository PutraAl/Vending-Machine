import json


class MQTTSubscriber:
    def __init__(self, mqtt_client):
        self.mqtt = mqtt_client
        self.mqtt.client.on_message = self._on_message

    def subscribe_commands(self):
        topic = "vending/machine/command"

        self.mqtt.client.subscribe(topic)

        print(
            f"[MQTT] Subscribed → {topic}"
        )

    def _on_message(self, client, userdata, message):
        raw_message = message.payload.decode("utf-8")

        print(
            f"[MQTT] Raw message: {raw_message}"
        )

        try:
            payload = json.loads(raw_message)

            print(
                f"[MQTT] Command received: "
                f"{payload}"
            )

        except json.JSONDecodeError as error:
            print(
                f"[MQTT] Invalid JSON received: "
                f"{error}"
            )