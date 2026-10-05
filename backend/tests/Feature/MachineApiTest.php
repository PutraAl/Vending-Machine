<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_machine(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/machines', [
                'machine_code' => 'VM001',
                'name' => 'Vending Machine Lobby',
                'location' => 'Gedung A - Lantai 1',
                'temperature_threshold' => 80,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.machine_code',
                'VM001'
            );

        $this->assertDatabaseHas('machines', [
            'machine_code' => 'VM001',
        ]);
    }

    public function test_non_admin_cannot_create_machine(): void
    {
        foreach (['technician', 'operator'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $response = $this->actingAs($user)
                ->postJson('/api/machines', [
                    'machine_code' => 'VM001',
                    'name' => 'Vending Machine',
                    'temperature_threshold' => 80,
                ]);

            $response->assertForbidden();
        }
    }

    public function test_all_roles_can_view_machines(): void
    {
        Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Vending Machine Lobby',
            'location' => 'Lobby',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);

        foreach (['admin', 'technician', 'operator'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $response = $this->actingAs($user)
                ->getJson('/api/machines');

            $response
                ->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    public function test_machine_can_be_filtered_by_status(): void
    {
        Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Machine Online',
            'status' => 'ONLINE',
            'temperature_threshold' => 80,
        ]);

        Machine::create([
            'machine_code' => 'VM002',
            'name' => 'Machine Offline',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);

        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/machines?status=ONLINE');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.machine_code',
                'VM001'
            );
    }

    public function test_machine_code_must_be_unique(): void
    {
        Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Existing Machine',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/machines', [
                'machine_code' => 'VM001',
                'name' => 'Another Machine',
                'temperature_threshold' => 80,
            ]);

        $response->assertUnprocessable();
    }

    public function test_temperature_threshold_cannot_be_negative(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/machines', [
                'machine_code' => 'VM001',
                'name' => 'Vending Machine',
                'temperature_threshold' => -1,
            ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_update_machine(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $machine = Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Old Name',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/machines/{$machine->id}", [
                'machine_code' => 'VM001',
                'name' => 'New Name',
                'status' => 'ONLINE',
                'temperature_threshold' => 85,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'New Name'
            )
            ->assertJsonPath(
                'data.status',
                'ONLINE'
            );
    }

    public function test_technician_cannot_update_machine(): void
    {
        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        $machine = Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Old Name',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/machines/{$machine->id}", [
                'machine_code' => 'VM001',
                'name' => 'New Name',
                'temperature_threshold' => 85,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_unused_machine(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $machine = Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Vending Machine',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/machines/{$machine->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('machines', [
            'id' => $machine->id,
        ]);
    }

    public function test_machine_cannot_be_deleted_when_it_has_slots(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $machine = Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Vending Machine',
            'status' => 'OFFLINE',
            'temperature_threshold' => 80,
        ]);

        $category = \App\Models\ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'price' => 15000,
            'is_active' => true,
        ]);

        $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/machines/{$machine->id}");

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }
}
