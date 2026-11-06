
import json
import ssl
import threading
import uuid
from getpass import getpass
from pathlib import Path

import paho.mqtt.client as mqtt


# ==========================================
# CONFIGURATION
# ==========================================

PROJECT_ROOT = Path(__file__).resolve().parents[1]
CA_FILE = (
    PROJECT_ROOT.parent
    / "MQTT"
    / "certs"
    / "ca-trust.crt"
)

HOST = "localhost"
PORT = 8883

COMMAND_TOPIC = "vending/machine/command"

MONITOR_TOPICS = [
    ("vending/machine/status", 1),
    ("vending/machine/temperature", 1),
    ("vending/machine/inventory", 1),
    ("vending/machine/error", 1),
]

connected = threading.Event()
subscribed = threading.Event()
error_received = threading.Event()
telemetry_received = threading.Event()

received_error = None


# ==========================================
# CALLBACKS
# ==========================================

def on_connect(client, userdata, flags, reason_code, properties):
    if reason_code.is_failure:
        print(f"[BACKEND] Connection rejected: {reason_code}")
        return

    print("[BACKEND] Connected securely using TLS.")

    result, mid = client.subscribe(MONITOR_TOPICS)

    if result != mqtt.MQTT_ERR_SUCCESS:
        print("[BACKEND] Failed to subscribe.")
        return

    connected.set()


def on_subscribe(client, userdata, mid, reason_codes, properties):
    if any(code.is_failure for code in reason_codes):
        print("[BACKEND] Subscription rejected:", reason_codes)
        return

    print("[BACKEND] Subscription successful.")
    subscribed.set()


def on_message(client, userdata, message):
    global received_error

    payload_text = message.payload.decode(
        "utf-8",
        errors="replace"
    )

    print(f"\n[BACKEND] Received topic: {message.topic}")
    print(f"[BACKEND] Payload: {payload_text}")

    if message.topic == "vending/machine/temperature":
        telemetry_received.set()

    if message.topic == "vending/machine/error":
        try:
            payload = json.loads(payload_text)
            received_error = payload.get("error")

            if received_error == "UNKNOWN_COMMAND:MQTT_TEST_PROBE":
                error_received.set()

        except json.JSONDecodeError:
            pass


# ==========================================
# MAIN
# ==========================================

def main():
    if not CA_FILE.is_file():
        raise FileNotFoundError(
            f"CA certificate missing: {CA_FILE}"
        )

    username = "vending_backend_test"
    password = getpass("Backend test password: ")

    client = mqtt.Client(
        callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
        client_id=f"vending-backend-probe-{uuid.uuid4().hex[:8]}",
        protocol=mqtt.MQTTv311,
    )

    client.username_pw_set(username, password)

    tls_context = ssl.create_default_context(
        cafile=str(CA_FILE)
    )

    tls_context.minimum_version = ssl.TLSVersion.TLSv1_2
    tls_context.check_hostname = True

    client.tls_set_context(tls_context)

    client.on_connect = on_connect
    client.on_subscribe = on_subscribe
    client.on_message = on_message

    try:
        print("[BACKEND] Connecting to MQTT broker...")

        client.connect(HOST, PORT, 60)
        client.loop_start()

        if not connected.wait(15):
            raise TimeoutError("Backend connection timeout.")

        if not subscribed.wait(15):
            raise TimeoutError("Backend subscription timeout.")

        print("\n[TEST] Waiting for simulator telemetry...")

        if telemetry_received.wait(10):
            print("[PASS] Simulator telemetry received.")
        else:
            print("[WARNING] No temperature telemetry received.")

        # This is intentionally NOT a DISPENSE command.
        probe_payload = {
            "command": "MQTT_TEST_PROBE"
        }

        print("\n[TEST] Sending harmless command probe...")

        info = client.publish(
            COMMAND_TOPIC,
            json.dumps(probe_payload),
            qos=1,
            retain=False,
        )

        info.wait_for_publish(timeout=10)

        if not info.is_published():
            raise TimeoutError("Command probe publish timeout.")

        print("[BACKEND] Probe successfully published.")

        print("\n[TEST] Waiting for simulator response...")

        if error_received.wait(10):
            print("[PASS] Simulator received probe and rejected unknown command.")
            print("[PASS] Simulator returned error to backend.")
        else:
            print("[WARNING] No expected simulator error received.")

        print("\n========== TEST SUMMARY ==========")
        print("TLS connection       : PASS")
        print("Backend authentication: PASS")
        print("Backend subscription : PASS")
        print(
            "Simulator telemetry :",
            "PASS" if telemetry_received.is_set() else "NOT CONFIRMED"
        )
        print(
            "Command response    :",
            "PASS" if error_received.is_set() else "NOT CONFIRMED"
        )
        print("==================================")

    finally:
        client.disconnect()
        client.loop_stop()


if __name__ == "__main__":
    main()
