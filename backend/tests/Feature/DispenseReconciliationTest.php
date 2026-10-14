<?php

namespace Tests\Feature;

use App\Models\Dispense;
use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispenseReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_outcome_does_not_change_stock(): void
    {
        
        $dispense = $this->createDispense();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/dispenses/{$dispense->id}/reconcile",
                [
                    'decision' => 'OUTCOME_UNKNOWN',
                    'reason' => 'Mesin belum memberikan hasil yang pasti.',
                ]
            )
            ->assertCreated();

        $this->assertDatabaseHas('dispenses', [
            'id' => $dispense->id,
            'status' => 'DISPENSING',
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $dispense->slot_id,
            'current_qty' => 8,
            'hold_qty' => 1,
        ]);

        $this->assertDatabaseHas('dispense_reconciliations', [
            'dispense_id' => $dispense->id,
            'user_id' => $admin->id,
            'decision' => 'OUTCOME_UNKNOWN',
        ]);
    }

    public function test_admin_can_reconcile_confirmed_success(): void
    {
        $dispense = $this->createDispense();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/dispenses/{$dispense->id}/reconcile",
                [
                    'decision' => 'CONFIRMED_COMPLETED',
                    'reason' => 'Produk dipastikan telah keluar.',
                    'evidence' => 'Pemeriksaan fisik mesin.',
                ]
            )
            ->assertCreated();

        $this->assertDatabaseHas('dispenses', [
            'id' => $dispense->id,
            'status' => 'COMPLETED',
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $dispense->slot_id,
            'current_qty' => 7,
            'hold_qty' => 0,
        ]);
    }

    public function test_admin_can_reconcile_confirmed_failure(): void
    {
        $dispense = $this->createDispense();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/dispenses/{$dispense->id}/reconcile",
                [
                    'decision' => 'CONFIRMED_FAILED',
                    'reason' => 'Produk dipastikan tidak keluar.',
                    'evidence' => 'Pemeriksaan fisik mesin.',
                ]
            )
            ->assertCreated();

        $this->assertDatabaseHas('dispenses', [
            'id' => $dispense->id,
            'status' => 'FAILED',
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $dispense->slot_id,
            'current_qty' => 8,
            'hold_qty' => 0,
        ]);
    }

    public function test_operator_cannot_reconcile(): void
    {
        $dispense = $this->createDispense();
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator, 'sanctum')
            ->postJson(
                "/api/v1/dispenses/{$dispense->id}/reconcile",
                [
                    'decision' => 'CONFIRMED_COMPLETED',
                    'reason' => 'Percobaan akses.',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('dispense_reconciliations', [
            'dispense_id' => $dispense->id,
        ]);
    }

    private function createDispense(): Dispense
    {
        $machine = Machine::create([
            'machine_code' => fake()->unique()->bothify('VM###'),
            'name' => 'Test Machine',
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
            'hold_qty' => 1,
        ]);

        $order = Order::create([
            'order_code' => 'TEST-' . strtoupper(uniqid()),
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

        return Dispense::create([
            'order_id' => $order->id,
            'machine_id' => $machine->id,
            'slot_id' => $slot->id,
            'status' => 'DISPENSING',
            'started_at' => now()->subMinutes(5),
        ]);
    }
}