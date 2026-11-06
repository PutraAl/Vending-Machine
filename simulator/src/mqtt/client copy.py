import os

import paho.mqtt.client as mqtt
from dotenv import load_dotenv


load_dotenv()


class MQTTClient:
    def __init__(self):
        self.broker = os.getenv("MQTT_BROKER", "localhost")
        self.port = int(os.getenv("MQTT_PORT", 1883))
        self.client_id = os.getenv(
            "MQTT_CLIENT_ID",
            "vending-simulator"
        )

        self.client = mqtt.Client(
            mqtt.CallbackAPIVersion.VERSION2,
            client_id=self.client_id
        )

        self.client.on_connect = self._on_connect
        self.client.on_disconnect = self._on_disconnect

    def _on_connect(self, client, userdata, flags, reason_code, properties):
        print(
            f"[MQTT] Connected to "
            f"{self.broker}:{self.port}"
        )
        print(f"[MQTT] Result: {reason_code}")

    def _on_disconnect(
        self,
        client,
        userdata,
        disconnect_flags,
        reason_code,
        properties
    ):
        print(f"[MQTT] Disconnected. Reason: {reason_code}")

    def connect(self):
        print(
            f"[MQTT] Connecting to "
            f"{self.broker}:{self.port}..."
        )

        self.client.connect(
            self.broker,
            self.port,
            60
        )

        self.client.loop_start()

    def disconnect(self):
        self.client.loop_stop()
        self.client.disconnect()

    def publish(self, topic, message):
        result = self.client.publish(
            topic,
            message
        )

        if result.rc == mqtt.MQTT_ERR_SUCCESS:
            print(
                f"[MQTT] Published → "
                f"{topic}: {message}"
            )
        else:
            print(
                f"[MQTT] Failed → "
                f"{topic}"
            )