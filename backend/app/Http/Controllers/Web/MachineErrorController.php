<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MachineError;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class MachineErrorController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', 'string', 'max:30'],
            'resolution' => ['nullable', 'in:all,open,resolved'],
        ]);

        $search = trim($validated['search'] ?? '');
        $severity = $validated['severity'] ?? '';
        $resolution = $validated['resolution'] ?? 'all';

        $severities = MachineError::query()
            ->whereNotNull('severity')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity');

        $totalErrors = MachineError::count();

        $unresolvedErrors = MachineError::whereNull('resolved_at')->count();

        $resolvedErrors = MachineError::whereNotNull('resolved_at')->count();

        $query = MachineError::with('machine');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('error_code', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('machine', function (Builder $machineQuery) use ($search) {
                        $machineQuery
                            ->where('machine_code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($severity !== '' && $severities->contains($severity)) {
            $query->where('severity', $severity);
        }

        if ($resolution === 'open') {
            $query->whereNull('resolved_at');
        } elseif ($resolution === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        $machineErrors = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('machine-errors.index', compact(
            'machineErrors',
            'severities',
            'search',
            'severity',
            'resolution',
            'totalErrors',
            'unresolvedErrors',
            'resolvedErrors'
        ));
    }


    public function resolve(
        Request $request,
        MachineError $machineError
    ): RedirectResponse {
        $role = strtolower((string) $request->user()?->role);

        abort_unless(
            in_array($role, ['admin', 'technician', 'teknisi'], true),
            403,
            'Kamu tidak memiliki izin untuk menyelesaikan error mesin.'
        );

        if ($machineError->resolved_at !== null) {
            return back()->with(
                'success',
                'Error ini sudah ditandai selesai sebelumnya.'
            );
        }

        $machineError->update([
            'resolved_at' => now(),
        ]);

        Log::info('Machine error manually resolved.', [
            'machine_error_id' => $machineError->id,
            'machine_id' => $machineError->machine_id,
            'resolved_by_user_id' => $request->user()->id,
        ]);

        return back()->with(
            'success',
            'Error berhasil ditandai selesai.'
        );
    }
}
