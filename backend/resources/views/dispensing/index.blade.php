
@extends('layouts.dashboard')

@section('title', 'Dispensing')

@section('content')
<div class="space-y-6">

    <div>
        <p class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
            Operations
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-white">
            Dispensing
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Pantau proses pengeluaran produk dari mesin vending.
        </p>
    </div>

    {{-- Summary cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Dispenses', 'value' => $totalDispenses, 'color' => 'text-white'],
            ['label' => 'Active', 'value' => $activeDispenses, 'color' => 'text-cyan-300'],
            ['label' => 'Completed', 'value' => $completedDispenses, 'color' => 'text-emerald-300'],
            ['label' => 'Failed', 'value' => $failedDispenses, 'color' => 'text-rose-300'],
        ] as $stat)
            <div class="panel rounded-2xl p-5">
                <p class="text-xs font-medium text-slate-500">
                    {{ $stat['label'] }}
                </p>
                <p class="mt-3 text-3xl font-semibold {{ $stat['color'] }}">
                    {{ number_format($stat['value']) }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Search and filter --}}
    <div class="panel rounded-2xl p-4">
        <form method="GET" action="{{ route('dispensing.index') }}"
            class="flex flex-col gap-3 sm:flex-row">

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari ID, kode order, mesin, slot, produk..."
                class="min-w-0 flex-1 rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >

            <select
                name="status"
                class="rounded-xl border border-white/[0.08] bg-[#0e1520] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >
                <option value="">Semua status</option>
                @foreach ($statuses as $availableStatus)
                    <option value="{{ $availableStatus }}"
                        @selected($status === $availableStatus)>
                        {{ $availableStatus }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                class="rounded-xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300">
                Filter
            </button>

            <a href="{{ route('dispensing.index') }}"
                class="rounded-xl border border-white/[0.08] px-5 py-3 text-center text-sm text-slate-300 hover:bg-white/[0.04]">
                Reset
            </a>
        </form>
    </div>

    {{-- Dispense table --}}
    <div class="panel overflow-hidden rounded-2xl">
        <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-white">
                    Dispense History
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Data proses dispense yang tercatat di database.
                </p>
            </div>
            <span class="text-xs text-slate-500">
                {{ $dispenses->total() }} data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-white/[0.06] bg-white/[0.02]">
                    <tr class="text-[10px] uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-4">Dispense</th>
                        <th class="px-5 py-4">Order / Machine</th>
                        <th class="px-5 py-4">Product / Slot</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Started</th>
                        <th class="px-5 py-4">Completed</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/[0.05]">
                    @forelse ($dispenses as $dispense)
                        @php
                            $normalizedStatus = strtoupper($dispense->status ?? 'UNKNOWN');

                            $badgeClass = match ($normalizedStatus) {
                                'COMPLETED' => 'bg-emerald-400/10 text-emerald-300',
                                'FAILED' => 'bg-rose-400/10 text-rose-300',
                                'DISPENSING' => 'bg-cyan-400/10 text-cyan-300',
                                'PENDING' => 'bg-amber-400/10 text-amber-300',
                                default => 'bg-slate-400/10 text-slate-300',
                            };

                            $isStale = in_array($normalizedStatus, ['PENDING', 'DISPENSING'], true)
                                && $dispense->started_at !== null
                                && $dispense->started_at->lte(now()->subMinutes(3));

                            $quantity = $dispense->order?->items?->first()?->quantity;
                        @endphp

                        <tr class="align-top hover:bg-white/[0.02]">
                            <td class="px-5 py-4">
                                <p class="font-medium text-white">
                                    #{{ $dispense->id }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $dispense->created_at?->format('d M Y H:i') ?? '—' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-slate-200">
                                    {{ $dispense->order?->order_code ?? 'Order tidak ditemukan' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $dispense->machine?->machine_code ?? 'Mesin tidak ditemukan' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-slate-200">
                                    {{ $dispense->slot?->product?->name ?? 'Produk tidak ditemukan' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Slot {{ $dispense->slot?->slot_code ?? '—' }}
                                    @if ($quantity !== null)
                                        · {{ $quantity }} unit
                                    @endif
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $badgeClass }}">
                                    {{ $normalizedStatus }}
                                </span>

                                @if ($isStale)
                                    <p class="mt-2 max-w-32 text-[10px] leading-4 text-amber-300">
                                        Perlu diperiksa: proses lebih dari 3 menit.
                                    </p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-300">
                                {{ $dispense->started_at?->format('d M Y H:i:s') ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-300">
                                {{ $dispense->completed_at?->format('d M Y H:i:s') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="text-sm text-slate-300">
                                    Belum ada data dispense.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Riwayat akan muncul ketika proses dispense tercatat.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-white/[0.06] px-5 py-4">
            {{ $dispenses->links() }}
        </div>
    </div>
</div>
@endsection
