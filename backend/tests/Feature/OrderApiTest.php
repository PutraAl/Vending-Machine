<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;
    private function createMachine(
        string $status = 'ONLINE'
    ): Machine {
        return Machine::create([
            'machine_code' => fake()->unique()->bothify('VM###'),
            'name' => 'Vending Machine',
            'location' => 'Lobby',
            'status' => $status,
            'temperature_threshold' => 80,
        ]);
    }

    private function createProduct(
        string $name = 'Nasi Goreng',
        float $price = 15000
    ): Product {
        $category = ProductCategory::firstOrCreate([
            'name' => 'Makanan',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function createSlot(
        Machine $machine,
        Product $product,
        string $slotCode = 'A01',
        int $stock = 5,
        int $capacity = 10
    ): MachineSlot {
        return $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => $slotCode,
            'stock' => $stock,
            'capacity' => $capacity,
        ]);
    }

    public function test_operator_can_create_order(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine();

        $product = $this->createProduct();

        $slot = $this->createSlot(
            $machine,
            $product
        );

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'machine_id' => $machine->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'slot_id' => $slot->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'PENDING'
            )
            ->assertJsonPath(
                'data.total_amount',
                '30000.00'
            );

        $this->assertDatabaseHas('orders', [
            'machine_id' => $machine->id,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'stock' => 5,
        ]);
    }

    public function test_technician_cannot_create_order(): void
    {
        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'machine_id' => $machine->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'slot_id' => $slot->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_order_fails_when_machine_is_offline(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine('OFFLINE');
        $product = $this->createProduct();
        $slot = $this->createSlot($machine, $product);

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'machine_id' => $machine->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'slot_id' => $slot->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertUnprocessable();
    }

    public function test_order_fails_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine();
        $product = $this->createProduct();
        $slot = $this->createSlot(
            $machine,
            $product,
            'A01',
            2,
            10
        );

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'machine_id' => $machine->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'slot_id' => $slot->id,
                        'quantity' => 3,
                    ],
                ],
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('orders', [
            'machine_id' => $machine->id,
        ]);

        $this->assertDatabaseHas('machine_slots', [
            'id' => $slot->id,
            'stock' => 2,
        ]);
    }

    public function test_order_fails_when_product_does_not_match_slot(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine();

        $productA = $this->createProduct(
            'Nasi Goreng'
        );

        $productB = $this->createProduct(
            'Mie Goreng'
        );

        $slot = $this->createSlot(
            $machine,
            $productA
        );

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'machine_id' => $machine->id,
                'items' => [
                    [
                        'product_id' => $productB->id,
                        'slot_id' => $slot->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertUnprocessable();
    }

    public function test_order_fails_when_slot_belongs_to_another_machine(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machineA = $this->createMachine();
        $machineB = $this->createMachine();

        $product = $this->createProduct();

        $slot = $this->createSlot(
            $machineB,
            $product
        );

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'machine_id' => $machineA->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'slot_id' => $slot->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertUnprocessable();
    }

    public function test_order_fails_when_product_is_inactive(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $machine = $this->createMachine();

        $product = $this->createProduct();

        $product->update([
            'is_active' => false,
        ]);

        $slot = $this->createSlot(
            $machine,
            $product
        );

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'machine_id' => $machine->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'slot_id' => $slot->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertUnprocessable();
    }

    public function test_all_roles_can_view_orders(): void
    {
        $machine = $this->createMachine();

        $product = $this->createProduct();

        $slot = $this->createSlot(
            $machine,
            $product
        );

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/orders', [
                'machine_id' => $machine->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'slot_id' => $slot->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertCreated();

        foreach (['admin', 'technician', 'operator'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $response = $this->actingAs($user)
                ->getJson('/api/orders');

            $response
                ->assertOk()
                ->assertJsonPath('success', true);
        }
    }
}
