<?php

namespace App\Services;

use App\Models\Dispense;
use App\Models\MachineSlot;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispenseService
{
    public function __construct(
        private readonly MqttService $mqttService
    ) {}

    public function startDispense(Order $order): Dispense
    {
        $dispense = DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== 'READY_TO_DISPENSE') {
                throw ValidationException::withMessages([
                    'order' => [
                        "Order cannot be dispensed from status {$lockedOrder->status}.",
                    ],
                ]);
            }

            $lockedOrder->load([
                'machine',
                'items.product',
                'items.slot',
            ]);

            if ($lockedOrder->machine->status !== 'ONLINE') {
                throw ValidationException::withMessages([
                    'order' => [
                        'Machine is currently offline.',
                    ],
                ]);
            }

            $existingDispense = Dispense::query()
                ->where('order_id', $lockedOrder->id)
                ->whereIn('status', [
                    'PENDING',
                    'DISPENSING',
                    'COMPLETED',
                ])
                ->exists();

            if ($existingDispense) {
                throw ValidationException::withMessages([
                    'order' => [
                        'This order already has a dispense process.',
                    ],
                ]);
            }

            if ($lockedOrder->items->count() !== 1) {
                throw ValidationException::withMessages([
                    'order' => [
                        'The current dispensing flow supports one order item at a time.',
                    ],
                ]);
            }

            $item = $lockedOrder->items->first();

            $slot = MachineSlot::query()
                ->whereKey($item->slot_id)
                ->where('machine_id', $lockedOrder->machine_id)
                ->lockForUpdate()
                ->first();

            if (! $slot) {
                throw ValidationException::withMessages([
                    'slot' => [
                        'Machine slot not found.',
                    ],
                ]);
            }

            if ($slot->hold_qty < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => [
                        'The reserved stock for this order is no longer available.',
                    ],
                ]);
            }

            $dispense = Dispense::create([
                'order_id' => $lockedOrder->id,
                'machine_id' => $lockedOrder->machine_id,
                'slot_id' => $slot->id,
                'status' => 'DISPENSING',
                'started_at' => now(),
            ]);

            $lockedOrder->update([
                'status' => 'DISPENSING',
            ]);

            return $dispense->fresh([
                'order',
                'machine',
                'slot.product',
            ]);
        });

        try {
            $this->mqttService->publishDispenseCommand(
                $dispense->id,
                $dispense->slot->slot_code,
                $dispense->order->items->first()->quantity
            );
        } catch (\Throwable $exception) {
            try {
                $this->failDispense($dispense);
            } catch (\Throwable $failureException) {
                report($failureException);
            }

            throw $exception;
        }

        return $dispense;
    }

    public function completeDispense(
        Dispense $dispense
    ): Dispense {
        return DB::transaction(function () use ($dispense) {
            $lockedDispense = Dispense::query()
                ->whereKey($dispense->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDispense->status === 'COMPLETED') {
                return $lockedDispense->fresh([
                    'order',
                    'machine',
                    'slot.product',
                ]);
            }

            if ($lockedDispense->status !== 'DISPENSING') {
                throw ValidationException::withMessages([
                    'dispense' => [
                        "Dispense cannot be completed from status {$lockedDispense->status}.",
                    ],
                ]);
            }

            $lockedDispense->load('order.items');

            $item = $lockedDispense->order
                ->items
                ->firstWhere(
                    'slot_id',
                    $lockedDispense->slot_id
                );

            if (! $item) {
                throw ValidationException::withMessages([
                    'dispense' => [
                        'Order item for this slot was not found.',
                    ],
                ]);
            }

            $slot = MachineSlot::query()
                ->whereKey($lockedDispense->slot_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->hold_qty < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => [
                        'Insufficient reserved stock while completing dispense.',
                    ],
                ]);
            }

            /*
             * Physical stock leaves the machine here.
             */
            $slot->decrement(
                'current_qty',
                $item->quantity
            );

            /*
             * Reservation is consumed here.
             */
            $slot->decrement(
                'hold_qty',
                $item->quantity
            );

            $lockedDispense->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);

            $lockedDispense->order()->update([
                'status' => 'COMPLETED',
            ]);

            return $lockedDispense->fresh([
                'order',
                'machine',
                'slot.product',
            ]);
        });
    }

    public function failDispense(
        Dispense $dispense
    ): Dispense {
        return DB::transaction(function () use ($dispense) {
            $lockedDispense = Dispense::query()
                ->whereKey($dispense->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDispense->status === 'FAILED') {
                return $lockedDispense->fresh([
                    'order',
                    'machine',
                    'slot.product',
                ]);
            }

            if (
                ! in_array(
                    $lockedDispense->status,
                    ['PENDING', 'DISPENSING'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'dispense' => [
                        "Dispense cannot be failed from status {$lockedDispense->status}.",
                    ],
                ]);
            }

            $lockedDispense->load('order.items');

            $item = $lockedDispense->order
                ->items
                ->firstWhere(
                    'slot_id',
                    $lockedDispense->slot_id
                );

            if ($item) {
                $slot = MachineSlot::query()
                    ->whereKey($lockedDispense->slot_id)
                    ->lockForUpdate()
                    ->first();

                if ($slot) {
                    /*
                     * Product was not dispensed.
                     * Release the reservation only.
                     */
                    $releaseQty = min(
                        $slot->hold_qty,
                        $item->quantity
                    );

                    if ($releaseQty > 0) {
                        $slot->decrement(
                            'hold_qty',
                            $releaseQty
                        );
                    }
                }
            }

            $lockedDispense->update([
                'status' => 'FAILED',
            ]);

            $lockedDispense->order()->update([
                'status' => 'FAILED',
            ]);

            return $lockedDispense->fresh([
                'order',
                'machine',
                'slot.product',
            ]);
        });
    }
}
