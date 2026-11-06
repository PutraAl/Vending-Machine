# Laravel on Vercel with MQTT devices

## Runtime split

Laravel is deployed through `backend/vercel.json` as a PHP request handler. A Vercel function handles a request and then may scale down; it is not the place to keep `php artisan mqtt:listen` running continuously. The listener uses an MQTT loop that stays open waiting for machine messages.

Use this flow:

```text
Vercel Laravel API --MQTT/TLS--> Public MQTT broker <--MQTT/TLS-- Machine
        ^                                  |
        |                                  +-- telemetry and dispense results
        |                                                |
        +---------------- shared database <--- always-on Laravel listener
```

The API publishes a dispense command to `vending/machine/command`. The machine replies on the existing status, temperature, inventory, and error topics. Run `php artisan mqtt:listen` as an always-on process on a VPS, server, or computer that remains online. Configure that process with the same database and broker settings as Laravel. It processes MQTT messages and writes telemetry and dispense results to the database; the Vercel website then reads the updated database records.

## Broker reachability

The broker must be reachable from both Vercel and the listener host, with DNS and a TLS listener (normally port `8883`). The checked-in local Mosquitto profiles bind to `127.0.0.1`, so they are only reachable from the same host. A broker used by Vercel and a remote machine needs a network-accessible listener, firewall rules, TLS certificate, password authentication, and ACLs that limit each client to its required topics. Do not expose the anonymous port `1883` to the public internet.

Use separate MQTT client IDs and credentials for Laravel publisher, Laravel listener, and each machine. Keep the broker password and any private keys in Vercel environment variables or the worker's private environment; do not commit them.

## Laravel environment on Vercel

Set the following in the Vercel project's Production environment. Use the actual broker hostname and credentials:

```dotenv
MQTT_BROKER=mqtt.example.com
MQTT_PORT=8883
MQTT_PROTOCOL=3.1.1
MQTT_PUBLISHER_CLIENT_ID=laravel-vending-publisher
MQTT_PUBLISHER_USERNAME=...
MQTT_PUBLISHER_PASSWORD=...
MQTT_TLS_ENABLED=true
MQTT_TLS_VERIFY_PEER=true
MQTT_TLS_VERIFY_PEER_NAME=true
MQTT_TLS_ALLOW_SELF_SIGNED=false
```

Use a broker certificate trusted by the PHP runtime. If a private CA is required, make its CA certificate available to the deployed function and set `MQTT_TLS_CA_FILE` to its deployed path. Never set `MQTT_TLS_VERIFY_PEER=false` in production.

The Laravel publisher sends QoS 1 commands with `machine_id` and a short expiry. The simulator/device must use the same registered machine ID and reject duplicate or expired commands.

## Listener host

Install the backend's Composer dependencies on an always-on host and provide its production database settings plus `MQTT_BROKER`, `MQTT_PORT`, `MQTT_LISTENER_CLIENT_ID`, `MQTT_LISTENER_USERNAME`, `MQTT_LISTENER_PASSWORD`, and verified TLS settings. Start and supervise the process from the `backend` directory:

```sh
php artisan mqtt:listen
```

The host must keep outbound TLS connections to the broker and database access available. Restart the process after deployment or when its connection is interrupted. Do not start this command as a Vercel request or cron function.

## Operational check

1. Confirm the Vercel function can connect to the public broker using its publisher credentials.
2. Confirm the always-on listener connects using its separate listener credentials.
3. Confirm the machine subscribes to `vending/machine/command` and publishes to the topics in `backend/config/mqtt.php`.
4. Create and pay an order through the existing API flow, then call the existing confirm-dispense endpoint.
5. Check broker logs, Laravel logs, and database telemetry/dispense records to confirm command delivery and the machine result.

The listener is required for the machine-to-website direction. Without an always-on consumer, Vercel can publish commands but machine telemetry and completion events will not be processed into Laravel's database.
