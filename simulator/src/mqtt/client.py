import os
import ssl
import threading
from pathlib import Path

import paho.mqtt.client as mqtt
from dotenv import load_dotenv


# ==========================================
# PROJECT CONFIGURATION
# ==========================================

PROJECT_ROOT = Path(__file__).resolve().parents[2]

ENV_FILE = PROJECT_ROOT / ".env"
load_dotenv(dotenv_path=ENV_FILE, override=False)


class MQTTClient:

    def __init__(self):

        # ======================================
        # BROKER CONFIGURATION
        # ======================================

        self.broker = os.getenv(
            "MQTT_BROKER",
            "localhost"
        ).strip()

        self.port = int(
            os.getenv("MQTT_PORT", "8883")
        )

        self.client_id = os.getenv(
            "MQTT_CLIENT_ID",
            "vending-simulator-machine-1"
        ).strip()

        self.keepalive = int(
            os.getenv("MQTT_KEEPALIVE", "60")
        )

        self.connect_timeout = int(
            os.getenv("MQTT_CONNECT_TIMEOUT", "15")
        )

        self.qos = int(
            os.getenv("MQTT_QOS", "1")
        )

        if not self.broker:
            raise ValueError("MQTT_BROKER cannot be empty.")

        if not self.client_id:
            raise ValueError("MQTT_CLIENT_ID cannot be empty.")

        if self.port < 1 or self.port > 65535:
            raise ValueError("Invalid MQTT port.")

        if self.qos not in (0, 1, 2):
            raise ValueError("MQTT_QOS must be 0, 1, or 2.")

        # ======================================
        # AUTHENTICATION
        # ======================================

        self.username = os.getenv(
            "MQTT_USERNAME"
        )

        self.password = os.getenv(
            "MQTT_PASSWORD"
        )

        if bool(self.username) != bool(self.password):
            raise ValueError(
                "MQTT_USERNAME and MQTT_PASSWORD must be configured together."
            )

        # ======================================
        # TLS CERTIFICATE
        # ======================================

        self.tls_enabled = os.getenv(
            "MQTT_TLS_ENABLED", "true"
        ).strip().lower() in ("1", "true", "yes", "on")

        if self.tls_enabled:
            raw_ca_path = os.getenv("MQTT_CA_CERT", "").strip()

            if raw_ca_path:
                self.ca_file = Path(raw_ca_path)

                if not self.ca_file.is_absolute():
                    self.ca_file = PROJECT_ROOT / self.ca_file

                if not self.ca_file.is_file():
                    raise FileNotFoundError(
                        f"MQTT CA certificate not found: {self.ca_file}"
                    )

        # ======================================
        # CONNECTION STATE
        # ======================================

        self._connected = threading.Event()
        self._connack_received = threading.Event()

        self._connect_error = None
        self._loop_started = False

        self._subscriptions = {}
        self._subscriptions_lock = threading.RLock()

        # ======================================
        # MQTT CLIENT
        # ======================================

        self.client = mqtt.Client(
            callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
            client_id=self.client_id,
            protocol=mqtt.MQTTv311,
        )

        # Account authentication
        if self.username:
            self.client.username_pw_set(self.username, self.password)

        # ======================================
        # TLS SECURITY
        # ======================================

        if self.tls_enabled:
            tls_context = (
                ssl.create_default_context(cafile=str(self.ca_file))
                if hasattr(self, "ca_file")
                else ssl.create_default_context()
            )

            tls_context.minimum_version = ssl.TLSVersion.TLSv1_2
            tls_context.verify_mode = ssl.CERT_REQUIRED
            tls_context.check_hostname = True

            self.client.tls_set_context(tls_context)

        # ======================================
        # RECONNECTION
        # ======================================

        self.client.reconnect_delay_set(
            min_delay=1,
            max_delay=30
        )

        # ======================================
        # CALLBACKS
        # ======================================

        self.client.on_connect = self._on_connect
        self.client.on_disconnect = self._on_disconnect
        self.client.on_connect_fail = self._on_connect_fail

    # ==========================================
    # CONNECTION CALLBACK
    # ==========================================

    def _on_connect(
        self,
        client,
        userdata,
        flags,
        reason_code,
        properties
    ):

        self._connack_received.set()

        if reason_code.is_failure:

            self._connect_error = str(reason_code)
            self._connected.clear()

            print(
                f"[MQTT] Connection rejected: {reason_code}"
            )

            return

        self._connect_error = None
        self._connected.set()

        print(
            f"[MQTT] Securely connected to "
            f"{self.broker}:{self.port}"
        )

        print("[MQTT] TLS certificate verification: ENABLED")
        print("[MQTT] Authentication: ENABLED")

        # Resubscribe after every successful reconnect
        with self._subscriptions_lock:
            subscriptions = list(
                self._subscriptions.items()
            )

        for topic, qos in subscriptions:
            self._subscribe_topic(topic, qos)

    # ==========================================
    # DISCONNECTION CALLBACK
    # ==========================================

    def _on_disconnect(
        self,
        client,
        userdata,
        disconnect_flags,
        reason_code,
        properties
    ):

        self._connected.clear()

        print(
            f"[MQTT] Disconnected. Reason: {reason_code}"
        )

    def _on_connect_fail(self, client, userdata):

        print(
            "[MQTT] Connection attempt failed. "
            "Paho will retry automatically."
        )

    # ==========================================
    # CONNECT
    # ==========================================

    def connect(self):

        print(
            f"[MQTT] Connecting securely to "
            f"{self.broker}:{self.port}..."
        )

        self._connack_received.clear()
        self._connect_error = None

        try:

            self.client.connect(
                self.broker,
                self.port,
                self.keepalive
            )

            self.client.loop_start()
            self._loop_started = True

            if not self._connack_received.wait(
                self.connect_timeout
            ):
                raise TimeoutError(
                    "MQTT connection timed out."
                )

            if self._connect_error:

                raise ConnectionError(
                    f"MQTT authentication or connection "
                    f"rejected: {self._connect_error}"
                )

            if not self._connected.is_set():

                raise ConnectionError(
                    "MQTT client is not connected."
                )

            print("[MQTT] Connection established.")

        except Exception:

            if self._loop_started:
                self.client.disconnect()
                self.client.loop_stop()
                self._loop_started = False

            self._connected.clear()

            raise

    # ==========================================
    # SUBSCRIBE
    # ==========================================

    def _subscribe_topic(self, topic, qos):

        result, mid = self.client.subscribe(
            topic,
            qos=qos
        )

        if result != mqtt.MQTT_ERR_SUCCESS:

            print(
                f"[MQTT] Failed to subscribe: {topic}"
            )

            return False

        print(
            f"[MQTT] Subscription requested: "
            f"{topic} | QoS {qos}"
        )

        return True

    def subscribe(self, topic, qos=1):

        if qos not in (0, 1, 2):
            raise ValueError("Invalid subscription QoS.")

        with self._subscriptions_lock:
            self._subscriptions[topic] = qos

        if not self._connected.is_set():

            print(
                f"[MQTT] Subscription registered for "
                f"next connection: {topic}"
            )

            return True

        return self._subscribe_topic(topic, qos)

    # ==========================================
    # PUBLISH
    # ==========================================

    def publish(self, topic, message, qos=None):

        if not self._connected.is_set():

            raise ConnectionError(
                "Cannot publish: MQTT is disconnected."
            )

        selected_qos = self.qos if qos is None else qos

        if selected_qos not in (0, 1, 2):
            raise ValueError("Invalid publish QoS.")

        # All simulator messages use retain=False.
        result = self.client.publish(
            topic,
            message,
            qos=selected_qos,
            retain=False
        )

        if result.rc != mqtt.MQTT_ERR_SUCCESS:

            raise RuntimeError(
                f"Failed to queue MQTT message: {topic}. "
                f"Error code: {result.rc}"
            )

        print(
            f"[MQTT] Publish queued → "
            f"{topic} | QoS {selected_qos}"
        )

        # Do not block waiting for PUBACK here.
        # This method may be called inside on_message callbacks.
        return result

    # ==========================================
    # DISCONNECT
    # ==========================================

    def disconnect(self):

        if self._loop_started:

            print("[MQTT] Disconnecting...")

            try:
                self.client.disconnect()
            finally:
                self.client.loop_stop()
                self._loop_started = False

        self._connected.clear()

        print("[MQTT] Client stopped.")
