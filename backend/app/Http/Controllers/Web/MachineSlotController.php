<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMachineSlotRequest;
use App\Http\Requests\UpdateMachineSlotRequest;
use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MachineSlotController extends Controller
{
    public function index(): View
    {
        $slots = MachineSlot::query()
            ->with(['machine', 'product'])
            ->latest()
            ->get();

        $machines = Machine::query()
            ->orderBy('machine_code')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock-slots.index', compact(
            'slots',
            'machines',
            'products'
        ));
    }

    public function store(
        StoreMachineSlotRequest $request
    ): RedirectResponse {
        MachineSlot::create($request->validated());

        return redirect()
            ->route('stock-slots.index')
            ->with('success', 'Slot berhasil ditambahkan.');
    }

    public function update(
        UpdateMachineSlotRequest $request,
        MachineSlot $slot
    ): RedirectResponse {
        $slot->update($request->validated());

        return redirect()
            ->route('stock-slots.index')
            ->with('success', 'Slot berhasil diperbarui.');
    }

    public function destroy(
        MachineSlot $slot
    ): RedirectResponse {
        try {
            $slot->delete();

            return redirect()
                ->route('stock-slots.index')
                ->with('success', 'Slot berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('stock-slots.index')
                ->with(
                    'error',
                    'Slot tidak dapat dihapus karena masih digunakan oleh data lain.'
                );
        }
    }
}