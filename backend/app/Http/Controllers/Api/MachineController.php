<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMachineRequest;
use App\Http\Requests\UpdateMachineRequest;
use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $machines = Machine::query()
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search');

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where(
                                'machine_code',
                                'ILIKE',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'name',
                                'ILIKE',
                                '%' . $search . '%'
                            );
                    });
                }
            )
            ->when(
                $request->filled('status'),
                function ($query) use ($request) {
                    $query->where(
                        'status',
                        strtoupper($request->string('status'))
                    );
                }
            )
            ->orderBy('machine_code')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Machines retrieved successfully',
            'data' => $machines,
        ]);
    }

    public function store(
        StoreMachineRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $data['status'] ??= 'OFFLINE';

        $machine = Machine::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Machine created successfully',
            'data' => $machine,
        ], 201);
    }

    public function show(Machine $machine): JsonResponse
    {
        $machine->loadCount('slots');

        return response()->json([
            'success' => true,
            'message' => 'Machine retrieved successfully',
            'data' => $machine,
        ]);
    }

    public function update(
        UpdateMachineRequest $request,
        Machine $machine
    ): JsonResponse {
        $machine->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Machine updated successfully',
            'data' => $machine->fresh(),
        ]);
    }

    public function destroy(Machine $machine): JsonResponse
    {
        if (
            $machine->slots()->exists()
            || $machine->orders()->exists()
            || $machine->telemetries()->exists()
            || $machine->errors()->exists()
            || $machine->dispenses()->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Machine cannot be deleted because it has related operational data.',
            ], 409);
        }

        $machine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Machine deleted successfully',
            'data' => null,
        ]);
    }
}