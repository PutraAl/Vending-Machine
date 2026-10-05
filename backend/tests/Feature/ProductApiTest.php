<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Nasi Goreng',
                'description' => 'Nasi goreng panas',
                'price' => 15000,
                'is_active' => true,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.name',
                'Nasi Goreng'
            );

        $this->assertDatabaseHas('products', [
            'name' => 'Nasi Goreng',
            'category_id' => $category->id,
        ]);
    }

    public function test_operator_can_create_product(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Mie Goreng',
                'price' => 13000,
            ]);

        $response->assertCreated();
    }

    public function test_technician_cannot_create_product(): void
    {
        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Mie Goreng',
                'price' => 13000,
            ]);

        $response->assertForbidden();
    }

    public function test_all_roles_can_view_products(): void
    {
        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'price' => 15000,
            'is_active' => true,
        ]);

        foreach (['admin', 'technician', 'operator'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $response = $this->actingAs($user)
                ->getJson('/api/products');

            $response
                ->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    public function test_product_requires_valid_category(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'category_id' => 999999,
                'name' => 'Nasi Goreng',
                'price' => 15000,
            ]);

        $response->assertUnprocessable();
    }

    public function test_product_price_cannot_be_negative(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Nasi Goreng',
                'price' => -1000,
            ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_update_product(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
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

        $response = $this->actingAs($user)
            ->putJson("/api/products/{$product->id}", [
                'category_id' => $category->id,
                'name' => 'Nasi Goreng Spesial',
                'price' => 18000,
                'is_active' => true,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Nasi Goreng Spesial'
            );
    }

    public function test_technician_cannot_update_product(): void
    {
        $user = User::factory()->create([
            'role' => 'technician',
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

        $response = $this->actingAs($user)
            ->putJson("/api/products/{$product->id}", [
                'category_id' => $category->id,
                'name' => 'Nasi Goreng Baru',
                'price' => 16000,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_unused_product(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
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

        $response = $this->actingAs($user)
            ->deleteJson("/api/products/{$product->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_cannot_be_deleted_when_used_by_machine_slot(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
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

        $machine = \App\Models\Machine::create([
            'machine_code' => 'VM001',
            'name' => 'Vending Machine',
            'location' => 'Lobby',
            'status' => 'ONLINE',
            'temperature_threshold' => 80,
        ]);

        $machine->slots()->create([
            'product_id' => $product->id,
            'slot_code' => 'A01',
            'stock' => 5,
            'capacity' => 10,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/products/{$product->id}");

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }
}