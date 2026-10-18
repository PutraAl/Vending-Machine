<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Telemetry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelemetryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'type' => ['nullable', 'in:temperature,state,door_status'],
        ]);

        $search = trim($validated['search'] ?? '');
        $machineId = $validated['machine_id'] ?? null;
        $type = $validated['type'] ?? '';

        $machines = Machine::query()
            ->orderBy('machine_code')
            ->get(['id', 'machine_code', 'name']);

        $totalRecords = Telemetry::count();

        $temperatureRecordsToday = Telemetry::query()
            ->whereNotNull('temperature')
            ->whereDate('created_at', today())
            ->count();

        $latestTemperature = Telemetry::query()
            ->whereNotNull('temperature')
            ->latest('created_at')
            ->first();

        $machinesReporting = Machine::query()
            ->whereHas('telemetries', function (Builder $query) {
                $query->where(
                    'created_at',
                    '>=',
                    now()->subSeconds(15)
                );
            })
            ->count();

        $query = Telemetry::with('machine');

        if ($machineId !== null) {
            $query->where('machine_id', $machineId);
        }

        if ($type !== '') {
            $query->whereNotNull($type);
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->whereHas('machine', function (Builder $machineQuery) use ($search) {
                        $machineQuery
                            ->where('machine_code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('door_status', 'like', "%{$search}%");
            });
        }

        $telemetries = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('telemetry.index', compact(
            'machines',
            'totalRecords',
            'temperatureRecordsToday',
            'latestTemperature',
            'machinesReporting',
            'telemetries',
            'search',
            'machineId',
            'type'
        ));
    }
}
