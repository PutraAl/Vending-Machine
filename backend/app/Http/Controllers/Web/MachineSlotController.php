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
use App\Http\Requests\StoreWebMachineSlotRequest;
use Illuminate\Support\Facades\DB;

class MachineSlotController extends Controller
{
    // Halaman awal: pilih mesin.
    public function index(): View
    {
        $machines = Machine::query()
            ->withCount('slots')
            ->with('slots')
            ->orderBy('machine_code')
            ->get();

        return view('stock-slots.index', compact('machines'));
    }

    // Halaman detail slot milik mesin terpilih.
    public function showMachine(Machine $machine): View
    {
        $nextSlotCode = $this->generateNextSlotCode($machine);
        $slots = $machine->slots()
            ->with(['product.category'])
            ->orderBy('slot_code')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock-slots.show', compact(
            'machine',
            'slots',
            'products',
            'nextSlotCode'
        ));
    }

    // Tambah slot untuk mesin terpilih.
    public function storeForMachine(
        StoreWebMachineSlotRequest $request,
        Machine $machine
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($machine, $data) {
            // Mengunci mesin selama proses pembuatan kode.
            Machine::query()
                ->whereKey($machine->id)
                ->lockForUpdate()
                ->firstOrFail();

            $slotCode = $this->generateNextSlotCode($machine);

            $machine->slots()->create([
                ...$data,
                'slot_code' => $slotCode,
            ]);
        });

        return redirect()
            ->route('stock-slots.machine', [
                'machine' => $machine->id,
            ])
            ->with('success', 'Slot berhasil ditambahkan.');
    }
    // Edit slot tanpa memindahkannya ke mesin lain.
    public function update(
        UpdateMachineSlotRequest $request,
        MachineSlot $slot
    ): RedirectResponse {
        $data = $request->validated();

        if ((int) $data['machine_id'] !== (int) $slot->machine_id) {
            return back()->with(
                'error',
                'Slot tidak dapat dipindahkan ke mesin lain melalui formulir ini.'
            );
        }

        // Stok fisik tidak boleh lebih kecil dari stok yang direservasi.
        if ((int) $data['current_qty'] < $slot->hold_qty) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Stok tidak boleh lebih rendah dari jumlah yang sedang direservasi.'
                );
        }

        // Machine ID tidak perlu diubah pada proses edit.
        unset($data['machine_id']);

        $slot->update($data);

        return redirect()
            ->route('stock-slots.machine', ['machine' => $slot->machine_id])
            ->with('success', 'Slot berhasil diperbarui.');
    }

    // Hapus slot dengan pemeriksaan stok dan reservasi.
    public function destroy(MachineSlot $slot): RedirectResponse
    {
        $machineId = $slot->machine_id;

        if ($slot->hold_qty > 0) {
            return redirect()
                ->route('stock-slots.machine', ['machine' => $machineId])
                ->with(
                    'error',
                    'Slot tidak dapat dihapus karena masih memiliki stok yang direservasi.'
                );
        }

        if ($slot->current_qty > 0) {
            return redirect()
                ->route('stock-slots.machine', ['machine' => $machineId])
                ->with(
                    'error',
                    'Kosongkan stok slot terlebih dahulu sebelum menghapusnya.'
                );
        }

        try {
            $slot->delete();

            return redirect()
                ->route('stock-slots.machine', ['machine' => $machineId])
                ->with('success', 'Slot berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('stock-slots.machine', ['machine' => $machineId])
                ->with(
                    'error',
                    'Slot tidak dapat dihapus karena masih digunakan oleh data lain.'
                );
        }
    }

    private function generateNextSlotCode(Machine $machine): string
    {
        $highestNumber = 0;

        $existingCodes = $machine->slots()->pluck('slot_code');

        foreach ($existingCodes as $code) {
            if (preg_match('/^A(\d+)$/i', $code, $matches)) {
                $highestNumber = max(
                    $highestNumber,
                    (int) $matches[1]
                );
            }
        }

        return 'A' . str_pad(
            (string) ($highestNumber + 1),
            2,
            '0',
            STR_PAD_LEFT
        );
    }
}
