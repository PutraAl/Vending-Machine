<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_operator_can_start_dispense(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine();
        $product = $this->createProduct();

        $slot = $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $response = $this->actingAs($user)
            ->postJson(
                "/api/orders/{$order->id}/dispense"
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'PENDING'
            );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'DISPENSING',
        ]);

        $this->assertDatabaseHas('dispenses', [
            'order_id' => $order->id,
            'machine_id' => $machine->id,
            'slot_id' => $slot->id,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'stock' => 5,
        ]);
    }

    public function test_dispense_cannot_start_from_pending_order(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine();
        $product = $this->createProduct();

        $slot = $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-' . fake()->unique()->numerify('######'),
            'machine_id' => $machine->id,
            'status' => 'PENDING',
            'total_amount' => 15000,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'slot_id' => $slot->id,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/orders/{$order->id}/dispense"
            );

        $response->assertUnprocessable();
    }

    public function test_dispense_cannot_start_twice(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine();
        $product = $this->createProduct();

        $slot = $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $order = $this->createReadyOrder(
            $machine,
            $product,
            $slot
        );

        $this->actingAs($user)
            ->postJson(
                "/api/orders/{$order->id}/dispense"
            )
            ->assertCreated();

        $response = $this->actingAs($user)
            ->postJson(
                "/api/orders/{$order->id}/dispense"
            );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('dispenses', 1);
    }
}