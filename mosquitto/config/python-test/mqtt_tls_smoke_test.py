"""Connect to the local Mosquitto TLS listener and observe existing topics.
This test does not publish messages and does not execute dispense commands.
"""
from __future__ import annotations

import getpass
import os
import ssl
import sys
from pathlib import Path

import paho.mqtt.client as mqtt
from dotenv import load_dotenv

HERE = Path(__file__).resolve().parent
load_dotenv(HERE / ".env")

host = os.getenv("MQTT_HOST", "localhost").strip()
port = int(os.getenv("MQTT_PORT", "8883"))
username = os.getenv("MQTT_USERNAME", "vending_simulator_3").strip()
password = os.getenv("MQTT_PASSWORD", "")
ca_value = os.getenv("MQTT_CA_CERT", "C:/vending-mqtt/certs/ca.crt").strip()
ca_path = Path(ca_value)
client_id = os.getenv("MQTT_CLIENT_ID", "vending-security-smoke-test").strip()

if not username:
    sys.exit("MQTT_USERNAME kosong. Isi .env terlebih dahulu.")
if not password:
    password = getpass.getpass(f"Password MQTT untuk {username}: ")
if not ca_path.is_file():
    sys.exit(f"CA certificate tidak ditemukan: {ca_path}. Periksa MQTT_CA_CERT di .env.")

# Only subscribe; no publishing, and no command execution.
TOPICS = [
    "vending/machine/command",
    "vending/machine/status",
    "vending/machine/telemetry",
    "vending/machine/error",
    "vending/machine/temperature",
    "vending/machine/stock",
    "vending/machine/door",
]

def on_connect(client, userdata, flags, reason_code, properties):
    if getattr(reason_code, "is_failure", False):
        print(f"MQTT connection rejected: {reason_code}")
        return
    print(f"Connected with TLS to {host}:{port} as {username}; broker reason={reason_code}")
    for topic in TOPICS:
        result, mid = client.subscribe(topic, qos=0)
        print(f"Subscribe requested: {topic} (result={result})")

def on_subscribe(client, userdata, mid, reason_code_list, properties):
    print(f"Subscribe acknowledgement: {reason_code_list}")

def on_message(client, userdata, message):
    # Payloads may contain operational data; avoid printing secrets if payloads change.
    print(f"[{message.topic}] {message.payload.decode('utf-8', errors='replace')}")

def on_disconnect(client, userdata, disconnect_flags, reason_code, properties):
    print(f"Disconnected: {reason_code}")

client = mqtt.Client(
    callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
    client_id=client_id,
    protocol=mqtt.MQTTv311,
    clean_session=True,
)
client.username_pw_set(username, password)
client.tls_set(
    ca_certs=str(ca_path),
    cert_reqs=ssl.CERT_REQUIRED,
    tls_version=ssl.PROTOCOL_TLS_CLIENT,
)
client.tls_insecure_set(False)  # Keep hostname and certificate verification enabled.
client.on_connect = on_connect
client.on_subscribe = on_subscribe
client.on_message = on_message
client.on_disconnect = on_disconnect

try:
    print("Connecting. Press Ctrl+C to stop. This test does not send dispense commands.")
    client.connect(host, port, keepalive=60)
    client.loop_forever()
except KeyboardInterrupt:
    print("Stopping test...")
except (OSError, ValueError, ssl.SSLError) as exc:
    sys.exit(f"Connection/configuration error: {exc}")
finally:
    try:
        client.disconnect()
    except Exception:
        pass
