
@extends('layouts.dashboard')

@section('title', 'Machine Errors')

@section('content')
<div class="space-y-6">
@if (session('success'))
    <div role="status"
        class="rounded-xl border border-emerald-400/20 bg-emerald-400/[0.05] px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif
    <div>
        <p class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
            Monitoring
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-white">
            Machine Errors
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Pantau error mesin, tingkat keparahan, dan status penyelesaiannya.
        </p>
    </div>

    {{-- Summary --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="panel rounded-2xl p-5">
            <p class="text-xs font-medium text-slate-500">Total Errors</p>
            <p class="mt-3 text-3xl font-semibold text-white">
                {{ number_format($totalErrors) }}
            </p>
            <p class="mt-2 text-[11px] text-slate-600">
                Seluruh error yang tercatat
            </p>
        </div>

        <div class="panel rounded-2xl p-5">
            <p class="text-xs font-medium text-slate-500">Unresolved</p>
            <p class="mt-3 text-3xl font-semibold text-rose-300">
                {{ number_format($unresolvedErrors) }}
            </p>
            <p class="mt-2 text-[11px] text-slate-600">
                Belum memiliki waktu penyelesaian
            </p>
        </div>

        <div class="panel rounded-2xl p-5">
            <p class="text-xs font-medium text-slate-500">Resolved</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-300">
                {{ number_format($resolvedErrors) }}
            </p>
            <p class="mt-2 text-[11px] text-slate-600">
                Memiliki catatan waktu penyelesaian
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="panel rounded-2xl p-4">
        <form method="GET" action="{{ route('machine-errors.index') }}"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari kode error, mesin, pesan..."
                class="min-w-0 rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >

            <select
                name="severity"
                class="rounded-xl border border-white/[0.08] bg-[#0e1520] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >
                <option value="">Semua severity</option>

                @foreach ($severities as $availableSeverity)
                    <option value="{{ $availableSeverity }}"
                        @selected($severity === $availableSeverity)>
                        {{ strtoupper($availableSeverity) }}
                    </option>
                @endforeach
            </select>

            <select
                name="resolution"
                class="rounded-xl border border-white/[0.08] bg-[#0e1520] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >
                <option value="all" @selected($resolution === 'all')>
                    Semua status
                </option>
                <option value="open" @selected($resolution === 'open')>
                    Unresolved
                </option>
                <option value="resolved" @selected($resolution === 'resolved')>
                    Resolved
                </option>
            </select>

            <div class="flex gap-2">
                <button type="submit"
                    class="flex-1 rounded-xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300">
                    Filter
                </button>

                <a href="{{ route('machine-errors.index') }}"
                    class="rounded-xl border border-white/[0.08] px-4 py-3 text-sm text-slate-300 hover:bg-white/[0.04]">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Error history --}}
    <div class="panel overflow-hidden rounded-2xl">
        <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-white">
                    Error History
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Daftar error yang tersimpan di database.
                </p>
            </div>

            <span class="text-xs text-slate-500">
                {{ $machineErrors->total() }} records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-white/[0.06] bg-white/[0.02]">
                    <tr class="text-[10px] uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-4">Error</th>
                        <th class="px-5 py-4">Machine</th>
                        <th class="px-5 py-4">Severity</th>
                        <th class="px-5 py-4">Resolution</th>
                        <th class="px-5 py-4">Detected At</th>
                        <th class="px-5 py-4">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/[0.05]">
                    @forelse ($machineErrors as $error)
                        @php
                            $normalizedSeverity = strtoupper($error->severity ?? 'UNKNOWN');

                            $severityClass = match ($normalizedSeverity) {
                                'CRITICAL', 'FATAL' => 'bg-rose-400/10 text-rose-300',
                                'HIGH', 'ERROR' => 'bg-orange-400/10 text-orange-300',
                                'WARNING', 'MEDIUM' => 'bg-amber-400/10 text-amber-300',
                                'LOW', 'INFO' => 'bg-cyan-400/10 text-cyan-300',
                                default => 'bg-slate-400/10 text-slate-300',
                            };
                        @endphp

                        <tr class="align-top hover:bg-white/[0.02]">
                            <td class="max-w-sm px-5 py-4">
                                <p class="font-semibold text-white">
                                    {{ $error->error_code ?? 'UNKNOWN' }}
                                </p>
                                <p class="mt-1 break-words text-xs leading-5 text-slate-400">
                                    {{ $error->message ?? 'Tidak ada detail pesan.' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-slate-200">
                                    {{ $error->machine?->machine_code ?? 'Unknown' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $error->machine?->name ?? 'Mesin tidak ditemukan' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $severityClass }}">
                                    {{ $normalizedSeverity }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if ($error->resolved_at)
                                    <span class="inline-flex rounded-full bg-emerald-400/10 px-2.5 py-1 text-[10px] font-semibold text-emerald-300">
                                        Resolved
                                    </span>
                                    <p class="mt-2 text-[10px] text-slate-500">
                                        {{ $error->resolved_at->format('d M Y H:i') }}
                                    </p>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-400/10 px-2.5 py-1 text-[10px] font-semibold text-rose-300">
                                        Unresolved
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-slate-300">
                                    {{ $error->created_at?->format('d M Y') ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $error->created_at?->format('H:i:s') ?? '' }}
                                </p>
                            </td>

                            
                            <td class="px-5 py-4">
                                @if ($error->resolved_at)
                                    <span class="text-xs text-emerald-300">
                                        Selesai
                                    </span>
                                @elseif (
                                    in_array(
                                        strtolower(auth()->user()->role ?? ''),
                                        ['admin', 'technician', 'teknisi'],
                                        true
                                    )
                                )
                                    <form
                                        method="POST"
                                        action="{{ route('machine-errors.resolve', $error) }}"
                                        onsubmit="return confirm('Pastikan masalah sudah diperiksa dan ditangani. Tandai error ini selesai?')"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="rounded-lg border border-emerald-400/20 px-3 py-2 text-xs font-medium text-emerald-300 hover:bg-emerald-400/[0.08]"
                                        >
                                            Tandai Selesai
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500">
                                        Khusus teknisi/admin
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <p class="text-sm text-slate-300">
                                    Tidak ada error yang ditemukan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Coba ubah filter atau periksa apakah error sudah tersimpan.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-white/[0.06] px-5 py-4">
            {{ $machineErrors->links() }}
        </div>
    </div>

</div>
@endsection
