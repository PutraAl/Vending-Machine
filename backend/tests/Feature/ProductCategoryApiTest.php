<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Makanan',
                'description' => 'Makanan panas',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Makanan',
        ]);
    }

    public function test_operator_can_create_category(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Snack',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_technician_cannot_create_category(): void
    {
        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Snack',
            ]);

        $response->assertForbidden();
    }

    public function test_any_authenticated_role_can_view_categories(): void
    {
        $user = User::factory()->create([
            'role' => 'technician',
        ]);

        ProductCategory::create([
            'name' => 'Makanan',
            'description' => 'Makanan panas',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/categories');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_category_name_must_be_unique(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Makanan',
            ]);

        $response->assertUnprocessable();
    }

    public function test_category_cannot_be_deleted_when_used_by_product(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = ProductCategory::create([
            'name' => 'Makanan',
        ]);

        $category->products()->create([
            'name' => 'Nasi Goreng',
            'price' => 15000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/categories/{$category->id}");

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }
}