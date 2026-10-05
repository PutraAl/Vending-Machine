<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.payment_integration.token'
            => 'test-payment-token',
        ]);
    }

    public function test_payment_confirmation_changes_order_to_ready_to_dispense(): void
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
            'stock' => 5,
            'capacity' => 10,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-TEST-001',
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

        $response = $this->withHeader(
            'X-Payment-Integration-Token',
            'test-payment-token'
        )->postJson('/api/integrations/payment/confirm', [
            'order_code' => 'ORD-TEST-001',
            'payment_reference' => 'PAY-001',
            'status' => 'PAID',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'READY_TO_DISPENSE'
            );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'READY_TO_DISPENSE',
        ]);
    }

    public function test_invalid_integration_token_is_rejected(): void
    {
        $response = $this
            ->withHeader(
                'X-Payment-Integration-Token',
                'wrong-token'
            )
            ->postJson('/api/integrations/payment/confirm', [
                'order_code' => 'ORD-001',
                'payment_reference' => 'PAY-001',
                'status' => 'PAID',
            ]);

        $response->assertUnauthorized();
    }
}
