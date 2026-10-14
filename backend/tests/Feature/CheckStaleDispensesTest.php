<?php

namespace Tests\Feature;

use App\Models\Dispense;
use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckStaleDispensesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_old_dispensing_records_are_reported_without_mutations(): void
    {
        $machine = Machine::create([
            'machine_code' => fake()->unique()->bothify('VM###'),
            'name' => 'Test Vending Machine',
            'location' => 'Testing',
            'status' => 'ONLINE',
            'temperature_threshold' => 80,
        ]);

        $category = ProductCategory::create([
            'name' => 'Test Food',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Meal',
            'price' => 15000,
            'is_active' => true,
        ]);

        $slot = MachineSlot::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'capacity' => 10,
            'current_qty' => 8,
            'hold_qty' => 3,
        ]);

        $stale = $this->createDispense(
            $machine,
            $slot,
            $product,
            'DISPENSING',
            now()->subMinutes(5),
        );

        $recent = $this->createDispense(
            $machine,
            $slot,
            $product,
            'DISPENSING',
            now()->subMinute(),
        );

        $completed = $this->createDispense(
            $machine,
            $slot,
            $product,
            'COMPLETED',
            now()->subMinutes(10),
        );

        $this->artisan('vending:check-stale-dispenses', [
            '--minutes' => 3,
        ])
            ->expectsOutputToContain(
                '1 stale dispense(s) found.'
            )
            ->expectsOutputToContain(
                'No stock or transaction status was changed.'
            )
            ->assertExitCode(0);

        $this->assertDatabaseHas('dispenses', [
            'id' => $stale->id,
            'status' => 'DISPENSING',
        ]);

        $this->assertDatabaseHas('dispenses', [
            'id' => $recent->id,
            'status' => 'DISPENSING',
        ]);

        $this->assertDatabaseHas('dispenses', [
            'id' => $completed->id,
            'status' => 'COMPLETED',
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'current_qty' => 8,
            'hold_qty' => 3,
        ]);
    }

    private function createDispense(
        Machine $machine,
        MachineSlot $slot,
        Product $product,
        string $status,
        $startedAt,
    ): Dispense {
        $orderStatus = $status === 'COMPLETED'
            ? 'COMPLETED'
            : 'DISPENSING';

        $order = Order::create([
            'order_code' => 'TEST-' . strtoupper(uniqid()),
            'machine_id' => $machine->id,
            'status' => $orderStatus,
            'total_amount' => 15000,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'slot_id' => $slot->id,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        return Dispense::create([
            'order_id' => $order->id,
            'machine_id' => $machine->id,
            'slot_id' => $slot->id,
            'status' => $status,
            'started_at' => $startedAt,
            'completed_at' => $status === 'COMPLETED'
                ? now()->subMinutes(9)
                : null,
        ]);
    }
}
