<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineSlotApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(
        string $name = 'Nasi Goreng'
    ): Product {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Makanan']
        );

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => 15000,
            'is_active' => true,
        ]);
    }

    private function createMachine(): Machine
    {
        return Machine::create([
            'machine_code' => fake()->unique()->bothify('VM###'),
            'name' => 'Vending Machine',
            'location' => 'Lobby',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);
    }

    public function test_all_roles_can_view_machine_slots(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        foreach (['admin', 'technician', 'operator'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $response = $this->actingAs($user)
                ->getJson(
                    "/api/machines/{$machine->id}/slots"
                );

            $response
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonCount(1, 'data');
        }
    }

    public function test_admin_can_create_slot(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/machines/{$machine->id}/slots",
                [
                    'product_id' => $product->id,
                    'slot_code' => 'A01',
                    'stock' => 5,
                    'capacity' => 10,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('data.slot_code', 'A01');

        $this->assertDatabaseHas('machine_slots', [
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);
    }

    public function test_operator_can_create_slot(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/machines/{$machine->id}/slots",
                [
                    'product_id' => $product->id,
                    'slot_code' => 'A01',
                    'stock' => 5,
                    'capacity' => 10,
                ]
            );

        $response->assertCreated();
    }

    public function test_technician_cannot_create_slot(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/machines/{$machine->id}/slots",
                [
                    'product_id' => $product->id,
                    'slot_code' => 'A01',
                    'stock' => 5,
                    'capacity' => 10,
                ]
            );

        $response->assertForbidden();
    }

    public function test_stock_cannot_exceed_capacity(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/machines/{$machine->id}/slots",
                [
                    'product_id' => $product->id,
                    'slot_code' => 'A01',
                    'stock' => 11,
                    'capacity' => 10,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stock');
    }

    public function test_slot_code_must_be_unique_per_machine(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();
        $anotherProduct = $this->createProduct('Mie Goreng');

        MachineSlot::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/machines/{$machine->id}/slots",
                [
                    'product_id' => $anotherProduct->id,
                    'slot_code' => 'A01',
                    'stock' => 5,
                    'capacity' => 10,
                ]
            );

        $response->assertStatus(409);
    }

    public function test_operator_can_update_stock(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $slot = MachineSlot::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(
                "/api/machines/{$machine->id}/slots/{$slot->id}/stock",
                [
                    'stock' => 8,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.stock', 8);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'stock' => 8,
        ]);
    }

    public function test_stock_update_cannot_exceed_capacity(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $slot = MachineSlot::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(
                "/api/machines/{$machine->id}/slots/{$slot->id}/stock",
                [
                    'stock' => 11,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'stock' => 5,
        ]);
    }

    public function test_technician_cannot_update_stock(): void
    {
        $machine = $this->createMachine();
        $product = $this->createProduct();

        $slot = MachineSlot::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(
                "/api/machines/{$machine->id}/slots/{$slot->id}/stock",
                [
                    'stock' => 8,
                ]
            );

        $response->assertForbidden();
    }

    public function test_slot_from_another_machine_cannot_be_accessed(): void
    {
        $machineA = $this->createMachine();
        $machineB = $this->createMachine();

        $product = $this->createProduct();

        $slot = MachineSlot::create([
            'machine_id' => $machineB->id,
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->getJson(
                "/api/machines/{$machineA->id}/slots/{$slot->id}"
            );

        $response->assertNotFound();
    }
}
