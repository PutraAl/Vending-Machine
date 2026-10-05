<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMachineRequest;
use App\Http\Requests\UpdateMachineRequest;
use App\Models\Machine;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MachineController extends Controller
{
    public function index(): View
    {
        $machines = Machine::query()
            ->withCount('slots')
            ->latest()
            ->get();

        return view('machines.index', compact('machines'));
    }

    public function store(StoreMachineRequest $request): RedirectResponse
    {
        Machine::create($request->validated());

        return redirect()
            ->route('machines.index')
            ->with('success', 'Machine berhasil ditambahkan.');
    }

    public function update(
        UpdateMachineRequest $request,
        Machine $machine
    ): RedirectResponse {
        $machine->update($request->validated());

        return redirect()
            ->route('machines.index')
            ->with('success', 'Machine berhasil diperbarui.');
    }

    public function destroy(Machine $machine): RedirectResponse
    {
        if ($machine->slots()->exists()) {
            return redirect()
                ->route('machines.index')
                ->with(
                    'error',
                    'Machine tidak dapat dihapus karena masih memiliki slot.'
                );
        }

        $machine->delete();

        return redirect()
            ->route('machines.index')
            ->with('success', 'Machine berhasil dihapus.');
    }
}