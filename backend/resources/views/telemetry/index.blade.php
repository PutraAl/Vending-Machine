
@extends('layouts.dashboard')

@section('title', 'Telemetry')

@section('content')
<div class="space-y-6">

    <div>
        <p class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
            Monitoring
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-white">
            Telemetry
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Riwayat suhu, status operasi, dan sensor pintu mesin.
        </p>
    </div>

    {{-- Summary --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="panel rounded-2xl p-5">
            <p class="text-xs text-slate-500">Total Records</p>
            <p class="mt-3 text-3xl font-semibold text-white">
                {{ number_format($totalRecords) }}
            </p>
            <p class="mt-2 text-[11px] text-slate-600">
                Seluruh data telemetry
            </p>
        </div>

        <div class="panel rounded-2xl p-5">
            <p class="text-xs text-slate-500">Recently Reporting</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-300">
                {{ number_format($machinesReporting) }}
            </p>
            <p class="mt-2 text-[11px] text-slate-600">
                Mesin dengan telemetry dalam 15 detik terakhir
            </p>
        </div>

        <div class="panel rounded-2xl p-5">
            <p class="text-xs text-slate-500">Latest Temperature</p>
            <p class="mt-3 text-3xl font-semibold text-white">
                {{ $latestTemperature?->temperature !== null
                    ? number_format((float) $latestTemperature->temperature, 1) . ' °C'
                    : '—' }}
            </p>
            <p class="mt-2 text-[11px] text-slate-600">
                {{ $latestTemperature?->created_at?->format('d M Y, H:i:s')
                    ?? 'Belum ada data suhu' }}
            </p>
        </div>

    </div>

    {{-- Filters --}}
    <div class="panel rounded-2xl p-4">
        <form method="GET" action="{{ route('telemetry.index') }}"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari mesin atau status..."
                class="min-w-0 rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >

            <select
                name="machine_id"
                class="rounded-xl border border-white/[0.08] bg-[#0e1520] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >
                <option value="">Semua mesin</option>
                @foreach ($machines as $machine)
                    <option value="{{ $machine->id }}"
                        @selected((string) $machineId === (string) $machine->id)>
                        {{ $machine->machine_code }} — {{ $machine->name }}
                    </option>
                @endforeach
            </select>

            <select
                name="type"
                class="rounded-xl border border-white/[0.08] bg-[#0e1520] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >
                <option value="">Semua data</option>
                <option value="temperature" @selected($type === 'temperature')>
                    Temperature
                </option>
                <option value="state" @selected($type === 'state')>
                    Machine State
                </option>
                <option value="door_status" @selected($type === 'door_status')>
                    Door Status
                </option>
            </select>

            <div class="flex gap-2">
                <button type="submit"
                    class="flex-1 rounded-xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300">
                    Filter
                </button>
                <a href="{{ route('telemetry.index') }}"
                    class="rounded-xl border border-white/[0.08] px-4 py-3 text-sm text-slate-300 hover:bg-white/[0.04]">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- History table --}}
    <div class="panel overflow-hidden rounded-2xl">

        <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-white">
                    Telemetry History
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Data sensor yang berhasil disimpan di database.
                </p>
            </div>

            <span class="text-xs text-slate-500">
                {{ $telemetries->total() }} records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-white/[0.06] bg-white/[0.02]">
                    <tr class="text-[10px] uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-4">Machine</th>
                        <th class="px-5 py-4">Temperature</th>
                        <th class="px-5 py-4">Machine State</th>
                        <th class="px-5 py-4">Door Status</th>
                        <th class="px-5 py-4">Received At</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/[0.05]">
                    @forelse ($telemetries as $telemetry)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-5 py-4">
                                <p class="font-medium text-white">
                                    {{ $telemetry->machine?->machine_code ?? 'Unknown' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $telemetry->machine?->name ?? 'Machine not found' }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($telemetry->temperature !== null)
                                    <span class="font-medium text-cyan-300">
                                        {{ number_format((float) $telemetry->temperature, 1) }} °C
                                    </span>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if ($telemetry->state !== null)
                                    <span class="text-slate-200">
                                        {{ $telemetry->state }}
                                    </span>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if ($telemetry->door_status !== null)
                                    <span class="text-slate-200">
                                        {{ $telemetry->door_status }}
                                    </span>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-slate-300">
                                    {{ $telemetry->created_at?->format('d M Y') ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $telemetry->created_at?->format('H:i:s') ?? '' }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <p class="text-sm text-slate-300">
                                    Belum ada data telemetry.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Pastikan mesin atau simulator mengirim data
                                    dan listener MQTT Laravel berjalan.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-white/[0.06] px-5 py-4">
            {{ $telemetries->links() }}
        </div>

    </div>
</div>
@endsection
