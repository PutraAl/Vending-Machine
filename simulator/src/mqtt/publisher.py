from mqtt.client import MQTTClient


class MQTTPublisher:
    def __init__(self, mqtt_client):
        self.mqtt = mqtt_client

    def publish_status(self, state):
        topic = "vending/machine/status"
        message = f'{{"state": "{state}"}}'

        self.mqtt.publish(topic, message)

    def publish_temperature(self, temperature, heater_on):
        topic = "vending/machine/temperature"

        message = (
            f'{{'
            f'"temperature": {temperature}, '
            f'"heater_on": {str(heater_on).lower()}'
            f'}}'
        )

        self.mqtt.publish(topic, message)

    def publish_stock(self, slot, product, stock):
        topic = "vending/machine/stock"

        message = (
            f'{{'
            f'"slot": "{slot}", '
            f'"product": "{product}", '
            f'"stock": {stock}'
            f'}}'
        )

        self.mqtt.publish(topic, message)

    def publish_error(self, error):
        topic = "vending/machine/error"
        message = f'{{"error": "{error}"}}'

        self.mqtt.publish(topic, message)