
import json
import ssl
import sys
import threading
from datetime import datetime, timezone
from getpass import getpass
from pathlib import Path

import paho.mqtt.client as mqtt


# ==========================================
# KONFIGURASI BROKER
# ==========================================

ROOT = Path(__file__).resolve().parents[1]

BROKER_HOST = "localhost"
BROKER_PORT = 8883
KEEPALIVE = 60

CA_FILE = ROOT / "certs" / "ca-trust.crt"

TELEMETRY_TOPIC = "vending/machine/telemetry"

BACKEND_USERNAME = "vending_backend_test"
SIMULATOR_USERNAME = "vending_simulator_test"

# Timeout untuk setiap proses koneksi
TIMEOUT = 15


# ==========================================
# VALIDASI FILE CA
# ==========================================

if not CA_FILE.is_file():
    raise FileNotFoundError(
        f"CA certificate tidak ditemukan: {CA_FILE}"
    )


# ==========================================
# CLIENT FACTORY
# Kompatibel dengan Paho MQTT 1.x dan 2.x
# ==========================================

def create_client(client_id, username, password):
    if hasattr(mqtt, "CallbackAPIVersion"):
        client = mqtt.Client(
            callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
            client_id=client_id,
            protocol=mqtt.MQTTv311,
        )
    else:
        client = mqtt.Client(
            client_id=client_id,
            protocol=mqtt.MQTTv311,
        )

    client.username_pw_set(username, password)

    # TLS: verifikasi CA dan hostname tetap aktif
    tls_context = ssl.create_default_context(
        cafile=str(CA_FILE)
    )

    tls_context.minimum_version = ssl.TLSVersion.TLSv1_2

    client.tls_set_context(tls_context)

    return client


def connect_success(reason_code):
    if hasattr(reason_code, "is_failure"):
        return not reason_code.is_failure

    return int(reason_code) == 0


# ==========================================
# EVENT
# ==========================================

backend_connected = threading.Event()
simulator_connected = threading.Event()
backend_subscribed = threading.Event()
message_received = threading.Event()

received_message = {}


# ==========================================
# BACKEND CALLBACKS
# ==========================================

def backend_on_connect(
    client, userdata, flags, reason_code, *extra
):
    if not connect_success(reason_code):
        print(f"[BACKEND] Koneksi ditolak: {reason_code}")
        return

    print("[BACKEND] Terhubung melalui TLS.")
    backend_connected.set()

    result, mid = client.subscribe(
        TELEMETRY_TOPIC,
        qos=1
    )

    if result != mqtt.MQTT_ERR_SUCCESS:
        print("[BACKEND] Gagal mengirim subscription.")


def backend_on_subscribe(
    client, userdata, mid, reason_codes, *extra
):
    if all(int(code) < 128 for code in reason_codes):
        print("[BACKEND] Subscription berhasil.")
        backend_subscribed.set()
    else:
        print("[BACKEND] Subscription ditolak:", reason_codes)


def backend_on_message(client, userdata, message):
    received_message["topic"] = message.topic
    received_message["payload"] = message.payload.decode(
        "utf-8", errors="replace"
    )

    print(f"[BACKEND] Pesan diterima: {message.topic}")
    message_received.set()


# ==========================================
# SIMULATOR CALLBACKS
# ==========================================

def simulator_on_connect(
    client, userdata, flags, reason_code, *extra
):
    if not connect_success(reason_code):
        print(f"[SIMULATOR] Koneksi ditolak: {reason_code}")
        return

    print("[SIMULATOR] Terhubung melalui TLS.")
    simulator_connected.set()


# ==========================================
# MAIN TEST
# ==========================================

def main():
    backend_password = getpass(
        "Password backend test: "
    )

    simulator_password = getpass(
        "Password simulator test: "
    )

    backend = create_client(
        "vending-backend-python-test",
        BACKEND_USERNAME,
        backend_password,
    )

    simulator = create_client(
        "vending-simulator-python-test",
        SIMULATOR_USERNAME,
        simulator_password,
    )

    backend.on_connect = backend_on_connect
    backend.on_subscribe = backend_on_subscribe
    backend.on_message = backend_on_message

    simulator.on_connect = simulator_on_connect

    clients = [backend, simulator]

    try:
        print("\n[TEST] Menghubungkan backend...")

        backend.connect(
            BROKER_HOST,
            BROKER_PORT,
            KEEPALIVE,
        )
        backend.loop_start()

        if not backend_connected.wait(TIMEOUT):
            raise TimeoutError(
                "Backend gagal terhubung."
            )

        if not backend_subscribed.wait(TIMEOUT):
            raise TimeoutError(
                "Backend gagal subscribe telemetry."
            )

        print("\n[TEST] Menghubungkan simulator...")

        simulator.connect(
            BROKER_HOST,
            BROKER_PORT,
            KEEPALIVE,
        )
        simulator.loop_start()

        if not simulator_connected.wait(TIMEOUT):
            raise TimeoutError(
                "Simulator gagal terhubung."
            )

        # Payload pengujian, bukan command dispense
        payload = {
            "test": True,
            "source": "python_simulator",
            "message": "mqtt_tls_auth_test",
            "sent_at": datetime.now(
                timezone.utc
            ).isoformat(),
        }

        print("\n[TEST] Mengirim telemetry...")

        info = simulator.publish(
            TELEMETRY_TOPIC,
            json.dumps(payload),
            qos=1,
            retain=False,
        )

        if info.rc != mqtt.MQTT_ERR_SUCCESS:
            raise RuntimeError(
                f"Publish gagal, kode: {info.rc}"
            )

        info.wait_for_publish(timeout=TIMEOUT)

        if not info.is_published():
            raise TimeoutError(
                "Tidak menerima konfirmasi publish."
            )

        if not message_received.wait(TIMEOUT):
            raise TimeoutError(
                "Backend tidak menerima telemetry."
            )

        received_payload = json.loads(
            received_message["payload"]
        )

        if received_payload.get("message") != "mqtt_tls_auth_test":
            raise RuntimeError(
                "Payload yang diterima tidak sesuai."
            )

        print("\n================================")
        print("MQTT PYTHON TLS TEST BERHASIL")
        print("================================")
        print("TLS verification : PASS")
        print("Backend auth     : PASS")
        print("Simulator auth   : PASS")
        print("Subscription     : PASS")
        print("Telemetry        : PASS")
        print("QoS              : 1")
        print("Retained         : False")

    except Exception as error:
        print(f"\n[TEST FAILED] {error}")
        sys.exit(1)

    finally:
        for client in clients:
            try:
                client.disconnect()
            except Exception:
                pass

        for client in clients:
            try:
                client.loop_stop()
            except Exception:
                pass


if __name__ == "__main__":
    main()
