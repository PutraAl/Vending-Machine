<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createOrder(
        int $machineId,
        array $items
    ): Order {
        if (count($items) !== 1) {
            throw ValidationException::withMessages([
                'items' => [
                    'An order must contain exactly one item.',
                ],
            ]);
        }

        return DB::transaction(function () use (
            $machineId,
            $items
        ) {

            $machine = Machine::query()
                ->whereKey($machineId)
                ->lockForUpdate()
                ->first();

            if (! $machine) {
                throw ValidationException::withMessages([
                    'machine_id' => [
                        'Machine not found.',
                    ],
                ]);
            }

            if ($machine->status !== 'ONLINE') {
                throw ValidationException::withMessages([
                    'machine_id' => [
                        'Machine is currently offline.',
                    ],
                ]);
            }

            $orderItems = [];
            $totalAmount = 0;

            foreach ($items as $item) {
                $slot = MachineSlot::query()
                    ->with('product')
                    ->where('machine_id', $machineId)
                    ->whereKey((int) $item['slot_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $slot) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Slot {$item['slot_id']} does not belong to the selected machine.",
                        ],
                    ]);
                }

                if ((int) $slot->product_id !== (int) $item['product_id']) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Product does not match slot {$slot->slot_code}.",
                        ],
                    ]);
                }

                if (! $slot->product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Product {$slot->product->name} is inactive.",
                        ],
                    ]);
                }

                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Quantity must be greater than zero.',
                        ],
                    ]);
                }

                $availableQty = $slot->availableQuantity();

                if ($availableQty < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Insufficient available stock for slot {$slot->slot_code}.",
                        ],
                    ]);
                }

                $price = (float) $slot->product->price;
                $subtotal = $price * $quantity;

                $orderItems[] = [
                    'product_id' => $slot->product_id,
                    'slot_id' => $slot->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];

                $totalAmount += $subtotal;

                // Reserve the physical stock for this order.
                $slot->increment('hold_qty', $quantity);
            }

            $order = Order::create([
                'order_code' => 'ORD-' . strtoupper((string) Str::ulid()),
                'machine_id' => $machineId,
                'status' => 'PENDING',
                'total_amount' => $totalAmount,
            ]);

            $order->items()->createMany($orderItems);

            return $order->load([
                'machine',
                'items.product',
                'items.slot',
            ]);
        });
    }

    public function confirmPayment(
        string $orderCode,
        string $paymentReference
    ): Order {
        return DB::transaction(function () use (
            $orderCode,
            $paymentReference
        ) {
            $order = Order::query()
                ->where('order_code', $orderCode)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_code' => [
                        'Order not found.',
                    ],
                ]);
            }

            if ($order->status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'order_code' => [
                        "Order cannot be paid because its current status is {$order->status}.",
                    ],
                ]);
            }

            $machine = $order->machine;

            if ($machine->status !== 'ONLINE') {
                throw ValidationException::withMessages([
                    'order_code' => [
                        'Machine is currently offline.',
                    ],
                ]);
            }

            $order->load('items');

            foreach ($order->items as $item) {
                $slot = MachineSlot::query()
                    ->whereKey($item->slot_id)
                    ->where('machine_id', $order->machine_id)
                    ->lockForUpdate()
                    ->first();

                if (! $slot) {
                    throw ValidationException::withMessages([
                        'order_code' => [
                            "Machine slot {$item->slot_id} was not found.",
                        ],
                    ]);
                }

                if ($slot->hold_qty < $item->quantity) {
                    throw ValidationException::withMessages([
                        'order_code' => [
                            "Stock reservation for slot {$slot->slot_code} is no longer available.",
                        ],
                    ]);
                }
            }

            /*
             * paymentReference is used as the idempotency key.
             *
             * The same key must not be reused for a different order.
             */
            $existingPayment = Payment::query()
                ->where('idempotency_key', $paymentReference)
                ->lockForUpdate()
                ->first();

            if ($existingPayment) {
                if ((int) $existingPayment->order_id !== (int) $order->id) {
                    throw ValidationException::withMessages([
                        'payment_reference' => [
                            'This payment reference has already been used for another order.',
                        ],
                    ]);
                }

                // Idempotent retry: return the already-confirmed order.
                return $order->fresh([
                    'machine',
                    'items.product',
                    'items.slot',
                    'payment',
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'idempotency_key' => $paymentReference,
            ]);

            $order->update([
                'status' => 'READY_TO_DISPENSE',
            ]);

            return $order->fresh([
                'machine',
                'items.product',
                'items.slot',
                'payment',
            ]);
        });
    }
}
