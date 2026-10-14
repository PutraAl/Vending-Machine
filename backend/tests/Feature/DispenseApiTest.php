<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Telemetry;
use App\Services\MqttService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class DispenseApiTest extends TestCase
{
    use RefreshDatabase;

    private function createMachine(): Machine
    {
        return Machine::create([
            'machine_code' => fake()->unique()->bothify('VM###'),
            'name' => 'Vending Machine',
            'location' => 'Lobby',
            'status' => 'ONLINE',
            'temperature_threshold' => 80,
        ]);
    }

    private function createProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'price' => 15000,
            'is_active' => true,
        ]);
    }

    private function createSlot(
        Machine $machine,
        Product $product
    ): MachineSlot {
        return $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'current_qty' => 5,
            'hold_qty' => 1,
            'capacity' => 10,
        ]);
    }

    private function createReadyOrder(
        Machine $machine,
        Product $product,
        MachineSlot $slot
    ): Order {
        $order = Order::create([
            'order_code' => 'ORD-' . fake()->unique()->numerify('######'),
            'machine_id' => $machine->id,
            'status' => 'READY_TO_DISPENSE',
            'total_amount' => 15000,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'slot_id' => $slot->id,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        return $order;
    }

    private function createPayment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'idempotency_key' => 'PAY-' . fake()->unique()->uuid(),
        ]);
    }

    private function createTemperatureTelemetry(
        Machine $machine,
        float $temperature,
        ?Carbon $createdAt = null
    ): Telemetry {
        return Telemetry::create([
            'machine_id' => $machine->id,
            'temperature' => $temperature,
            'state' => 'IDLE',
            'door_status' => null,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function mockDispenseCommand(): void
    {
        $this->mock(
            MqttService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('publishDispenseCommand')
                    ->once()
                    ->withArgs(
                        fn (
                            int $dispenseId,
                            string $slot,
                            int $quantity
                        ) => $dispenseId > 0
                            && $slot === 'A01'
                            && $quantity === 1
                    );
            }
        );
    }

    public function test_dispense_starts_with_safe_temperature(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $payment = $this->createPayment($order);

        $this->createTemperatureTelemetry($machine, 72.5);
        $this->mockDispenseCommand();

        $response = $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm-dispense"
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.order.status', 'DISPENSING')
            ->assertJsonPath('data.dispense.status', 'DISPENSING');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'DISPENSING',
        ]);

        $this->assertDatabaseHas('dispenses', [
            'order_id' => $order->id,
            'status' => 'DISPENSING',
        ]);

        // Stock is still reserved, not consumed, until completion.
        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 5,
            'hold_qty' => 1,
        ]);
    }

    public function test_dispense_is_rejected_when_temperature_exceeds_threshold(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $payment = $this->createPayment($order);

        $this->createTemperatureTelemetry($machine, 85.0);

        $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm-dispense"
        )->assertUnprocessable();

        $this->assertDatabaseMissing('dispenses', [
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'READY_TO_DISPENSE',
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 5,
            'hold_qty' => 1,
        ]);
    }

    public function test_dispense_is_rejected_when_temperature_telemetry_is_missing(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $payment = $this->createPayment($order);

        $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm-dispense"
        )->assertUnprocessable();

        $this->assertDatabaseMissing('dispenses', [
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 5,
            'hold_qty' => 1,
        ]);
    }

    public function test_dispense_is_rejected_when_temperature_telemetry_is_stale(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $payment = $this->createPayment($order);

        $this->createTemperatureTelemetry(
            $machine,
            72.5,
            now()->subSeconds(20)
        );

        $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm-dispense"
        )->assertUnprocessable();

        $this->assertDatabaseMissing('dispenses', [
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 5,
            'hold_qty' => 1,
        ]);
    }

    public function test_dispense_cannot_start_from_pending_order(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $order->update([
            'status' => 'PENDING',
        ]);

        $payment = $this->createPayment($order);

        $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm-dispense"
        )->assertUnprocessable();

        $this->assertDatabaseMissing('dispenses', [
            'order_id' => $order->id,
        ]);
    }

    public function test_dispense_cannot_start_twice(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $payment = $this->createPayment($order);

        $this->createTemperatureTelemetry($machine, 72.5);
        $this->mockDispenseCommand();

        $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm-dispense"
        )->assertCreated();

        $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm-dispense"
        )->assertUnprocessable();

        $this->assertDatabaseCount('dispenses', 1);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 5,
            'hold_qty' => 1,
        ]);
    }
}