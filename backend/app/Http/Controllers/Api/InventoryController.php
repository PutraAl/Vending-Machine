<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MachineSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $slots = MachineSlot::query()
            ->with([
                'machine',
                'product',
            ])
            ->when(
                $request->filled('machine_id'),
                function ($query) use ($request) {
                    $query->where(
                        'machine_id',
                        $request->integer('machine_id')
                    );
                }
            )
            ->when(
                $request->filled('slot_code'),
                function ($query) use ($request) {
                    $query->where(
                        'slot_code',
                        $request->string('slot_code')->toString()
                    );
                }
            )
            ->whereHas('product', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('machine_id')
            ->orderBy('slot_code')
            ->get();

        $inventory = $slots->map(function (MachineSlot $slot) {
            return [
                'machine' => [
                    'id' => $slot->machine->id,
                    'name' => $slot->machine->name,
                    'status' => $slot->machine->status,
                ],

                'slot' => [
                    'id' => $slot->id,
                    'slot_code' => $slot->slot_code,
                    'capacity' => $slot->capacity,
                ],

                'product' => [
                    'id' => $slot->product->id,
                    'name' => $slot->product->name,
                    'price' => $slot->product->price,
                    'image' => $slot->product->image,
                ],

                'current_qty' => $slot->current_qty,
                'hold_qty' => $slot->hold_qty,
                'available_qty' => $slot->availableQuantity(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Inventory retrieved successfully',
            'data' => $inventory,
        ]);
    }
}