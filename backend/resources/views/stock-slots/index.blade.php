@extends('layouts.dashboard')

@section('title', 'Slot & Stock')

@section('content')
<div class="space-y-6">

    <div>
        <p class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
            Inventory Management
        </p>

        <h1 class="mt-2 text-2xl font-semibold text-white">
            Slot & Stock
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Pilih mesin untuk melihat dan mengelola slot serta stoknya.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/[0.05] px-4 py-3 text-sm text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-rose-400/20 bg-rose-400/[0.05] px-4 py-3 text-sm text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-sm font-semibold text-white">
                Daftar Mesin
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                {{ $machines->count() }} mesin terdaftar
            </p>
        </div>

        <a href="{{ route('machines.index') }}"
            class="text-xs font-medium text-cyan-300 hover:text-cyan-200">
            Kelola Mesin →
        </a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2  xl:grid-cols-4">
        @forelse ($machines as $machine)
            @php
                $totalStock = $machine->slots->sum('current_qty');

                $availableStock = $machine->slots->sum(
                    fn ($slot) => $slot->availableQuantity()
                );

                $machineStatus = strtoupper($machine->status ?? 'UNKNOWN');

                $statusClass = match ($machineStatus) {
                    'ONLINE' => 'bg-emerald-400/10 text-emerald-300',
                    'OFFLINE' => 'bg-rose-400/10 text-rose-300',
                    default => 'bg-amber-400/10 text-amber-300',
                };
            @endphp

            <div class="panel panel-hover rounded-2xl p-5">

                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-400/[0.08] text-cyan-300">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" class="h-5 w-5">
                                <rect x="5" y="3" width="14" height="18" rx="2" />
                                <path stroke-linecap="round" d="M8 7h8M8 11h8M8 15h4" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-white">
                                {{ $machine->machine_code }}
                            </h3>

                            <p class="mt-1 truncate text-xs text-slate-500">
                                {{ $machine->name }}
                            </p>
                        </div>
                    </div>

                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $statusClass }}">
                        {{ $machineStatus }}
                    </span>
                </div>

                <div class="mt-4 border-t border-white/[0.06] pt-3">
                    <p class="text-xs text-slate-500">Lokasi</p>
                    <p class="mt-1 truncate text-sm text-slate-300">
                        {{ $machine->location ?: 'Belum ditentukan' }}
                    </p>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-white/[0.025] p-3">
                        <p class="text-[10px] text-slate-500">Slots</p>
                        <p class="mt-1 text-lg font-semibold text-white">
                            {{ $machine->slots_count }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/[0.025] p-3">
                        <p class="text-[10px] text-slate-500">Stok fisik</p>
                        <p class="mt-1 text-lg font-semibold text-white">
                            {{ $totalStock }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/[0.025] p-3">
                        <p class="text-[10px] text-slate-500">Siap jual</p>
                        <p class="mt-1 text-lg font-semibold text-cyan-300">
                            {{ $availableStock }}
                        </p>
                    </div>
                </div>

                <a href="{{ route('stock-slots.machine', ['machine' => $machine->id]) }}"
                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">

                    Kelola Slot & Stock

                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5 12h14m-7-7 7 7-7 7" />
                    </svg>
                </a>
            </div>
        @empty
            <div class="panel rounded-2xl p-8 sm:col-span-2 xl:col-span-3">
                <div class="text-center">
                    <p class="text-sm font-semibold text-white">
                        Belum ada mesin
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Tambahkan mesin terlebih dahulu sebelum mengatur slot dan stok.
                    </p>

                    <a href="{{ route('machines.index') }}"
                        class="mt-4 inline-flex rounded-xl bg-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-cyan-300">
                        Buka Manajemen Mesin
                    </a>
                </div>
            </div>
        @endforelse
    </div>

</div>
@endsection