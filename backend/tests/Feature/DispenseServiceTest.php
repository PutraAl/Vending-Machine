<?php

namespace Tests\Unit;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\DispenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DispenseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_dispense_decrements_stock_and_completes_order(): void
    {
        $machine = Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Vending Machine',
            'location' => 'Lobby',
            'status' => 'ONLINE',
            'temperature_threshold' => 80,
        ]);

        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'price' => 15000,
            'is_active' => true,
        ]);

        $slot = MachineSlot::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'current_qty' => 5,
            'hold_qty' => 1,
            'capacity' => 10,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-000001',
            'machine_id' => $machine->id,
            'status' => 'DISPENSING',
            'total_amount' => 15000,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'slot_id' => $slot->id,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        $dispense = $order->dispenses()->create([
            'machine_id' => $machine->id,
            'slot_id' => $slot->id,
            'status' => 'DISPENSING',
        ]);

        $service = app(DispenseService::class);

        // Penyelesaian pertama harus mengurangi stok.
        $service->completeDispense($dispense);

        // Retry untuk dispense yang sama tidak boleh mengurangi stok lagi.
        $service->completeDispense($dispense->fresh());

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 4,
            'hold_qty' => 0,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'COMPLETED',
        ]);

        $this->assertDatabaseHas('dispenses', [
            'id' => $dispense->id,
            'status' => 'COMPLETED',
        ]);
    }

    public function test_failed_dispense_does_not_change_stock(): void
    {
        $machine = Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Vending Machine',
            'location' => 'Lobby',
            'status' => 'ONLINE',
            'temperature_threshold' => 80,
        ]);

        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'price' => 15000,
            'is_active' => true,
        ]);

        $slot = MachineSlot::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'current_qty' => 5,
            'hold_qty' => 1,
            'capacity' => 10,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-000002',
            'machine_id' => $machine->id,
            'status' => 'DISPENSING',
            'total_amount' => 15000,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'slot_id' => $slot->id,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        $dispense = $order->dispenses()->create([
            'machine_id' => $machine->id,
            'slot_id' => $slot->id,
            'status' => 'DISPENSING',
        ]);

        $service = app(DispenseService::class);

        $service->failDispense($dispense);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 5,
            'hold_qty' => 0,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'FAILED',
        ]);
    }
}
