import time

from mqtt.client import MQTTClient
from mqtt.publisher import MQTTPublisher
from mqtt.subscriber import MQTTSubscriber


def main():
    mqtt_client = MQTTClient()
    mqtt_client.connect()

    publisher = MQTTPublisher(mqtt_client)
    subscriber = MQTTSubscriber(mqtt_client)

    # Subscribe command dari backend
    subscriber.subscribe_commands()

    # Beri waktu untuk memastikan subscription aktif
    time.sleep(1)

    # Initial machine state
    publisher.publish_status(
        state="IDLE"
    )

    # Initial temperature telemetry
    publisher.publish_temperature(
        temperature=72.5
    )

    # Initial inventory telemetry
    publisher.publish_inventory(
        slot="A01",
        product="Nasi Goreng",
        current_qty=5
    )

    print("\n[SIMULATOR] Running...")
    print("[SIMULATOR] Waiting for commands...")
    print("[SIMULATOR] Press CTRL+C to stop.")

    try:
        while True:
            time.sleep(1)

    except KeyboardInterrupt:
        print("\n[SIMULATOR] Stopping...")

    finally:
        mqtt_client.disconnect()


if __name__ == "__main__":
    main()