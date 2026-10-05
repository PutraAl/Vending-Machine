<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMachineSlotRequest;
use App\Http\Requests\UpdateMachineSlotRequest;
use App\Http\Requests\UpdateMachineSlotStockRequest;
use App\Models\Machine;
use App\Models\MachineSlot;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineSlotController extends Controller
{
    public function index(
        Request $request,
        Machine $machine
    ): JsonResponse {
        $slots = $machine->slots()
            ->with('product')
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search');

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where(
                                'slot_code',
                                'ILIKE',
                                '%' . $search . '%'
                            )
                            ->orWhereHas(
                                'product',
                                function ($query) use ($search) {
                                    $query->where(
                                        'name',
                                        'ILIKE',
                                        '%' . $search . '%'
                                    );
                                }
                            );
                    });
                }
            )
            ->orderBy('slot_code')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Machine slots retrieved successfully',
            'data' => $slots,
        ]);
    }

    public function store(
        StoreMachineSlotRequest $request,
        Machine $machine
    ): JsonResponse {
        $data = $request->validated();

        $data['machine_id'] = $machine->id;

        try {
            $slot = MachineSlot::create($data);
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot code already exists on this machine.',
                ], 409);
            }

            throw $exception;
        }

        $slot->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Machine slot created successfully',
            'data' => $slot,
        ], 201);
    }

    public function show(
        Machine $machine,
        MachineSlot $slot
    ): JsonResponse {
        $slot->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Machine slot retrieved successfully',
            'data' => $slot,
        ]);
    }

    public function update(
        UpdateMachineSlotRequest $request,
        Machine $machine,
        MachineSlot $slot
    ): JsonResponse {
        $data = $request->validated();

        try {
            $slot->update($data);
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot code already exists on this machine.',
                ], 409);
            }

            throw $exception;
        }

        $slot->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Machine slot updated successfully',
            'data' => $slot->fresh('product'),
        ]);
    }

    public function destroy(
        Machine $machine,
        MachineSlot $slot
    ): JsonResponse {
        if (
            $slot->orderItems()->exists()
            || $slot->dispenses()->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Machine slot cannot be deleted because it has related transaction data.',
            ], 409);
        }

        $slot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Machine slot deleted successfully',
            'data' => null,
        ]);
    }

    public function updateStock(
        UpdateMachineSlotStockRequest $request,
        Machine $machine,
        MachineSlot $slot
    ): JsonResponse {
        $newStock = (int) $request->validated()['stock'];

        $updatedSlot = DB::transaction(function () use (
            $machine,
            $slot,
            $newStock
        ) {
            $lockedSlot = MachineSlot::query()
                ->where('machine_id', $machine->id)
                ->whereKey($slot->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($newStock > $lockedSlot->capacity) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Stock cannot exceed slot capacity.',
                    'errors' => [
                        'stock' => [
                            'Stock cannot exceed capacity.',
                        ],
                    ],
                ], 422));
            }

            $lockedSlot->update([
                'stock' => $newStock,
            ]);

            return $lockedSlot->fresh('product');
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => $updatedSlot,
        ]);
    }
}
