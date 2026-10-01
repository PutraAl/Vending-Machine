import time

from mqtt.client import MQTTClient
from mqtt.publisher import MQTTPublisher
from mqtt.subscriber import MQTTSubscriber


def main():
    mqtt_client = MQTTClient()

    mqtt_client.connect()

    publisher = MQTTPublisher(mqtt_client)
    subscriber = MQTTSubscriber(mqtt_client)

    subscriber.subscribe_commands()

    time.sleep(1)

    publisher.publish_status("IDLE")

    publisher.publish_temperature(
        temperature=72.5,
        heater_on=True
    )

    publisher.publish_stock(
        slot="A01",
        product="Nasi Goreng",
        stock=5
    )

    print("\n[SIMULATOR] Running...")
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