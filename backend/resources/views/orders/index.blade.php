
@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
<div class="space-y-6">

    <div>
        <p class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
            Operations
        </p>
        <h1 class="mt-2 text-2xl font-semibold text-white">Orders</h1>
        <p class="mt-1 text-sm text-slate-500">
            Pantau pesanan dan status proses vending machine.
        </p>
    </div>

    <div class="rounded-2xl border border-white/[0.06] bg-[#0b111c] p-4">
        <form method="GET" action="{{ route('orders.index') }}"
            class="flex flex-col gap-3 sm:flex-row">

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari kode order atau mesin..."
                class="min-w-0 flex-1 rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >

            <select
                name="status"
                class="rounded-xl border border-white/[0.08] bg-[#0e1520] px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
            >
                <option value="">Semua status</option>

                @foreach ($statuses as $availableStatus)
                    <option
                        value="{{ $availableStatus }}"
                        @selected($status === $availableStatus)
                    >
                        {{ $availableStatus }}
                    </option>
                @endforeach
            </select>

            <button
                type="submit"
                class="rounded-xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300"
            >
                Filter
            </button>

            <a
                href="{{ route('orders.index') }}"
                class="rounded-xl border border-white/[0.08] px-5 py-3 text-center text-sm text-slate-300 hover:bg-white/[0.04]"
            >
                Reset
            </a>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-[#0b111c]">
        <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-4">
            <h2 class="text-sm font-semibold text-white">
                Order History
            </h2>
            <span class="text-xs text-slate-500">
                {{ $orders->total() }} pesanan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-white/[0.06] bg-white/[0.02]">
                    <tr class="text-[11px] uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-4">Order</th>
                        <th class="px-5 py-4">Mesin</th>
                        <th class="px-5 py-4">Produk</th>
                        <th class="px-5 py-4">Total</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Waktu</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/[0.05]">
                    @forelse ($orders as $order)
                        @php
                            $badgeClass = match (strtoupper($order->status ?? '')) {
                                'COMPLETED' => 'bg-emerald-400/10 text-emerald-300',
                                'FAILED', 'CANCELLED' => 'bg-rose-400/10 text-rose-300',
                                'DISPENSING' => 'bg-cyan-400/10 text-cyan-300',
                                default => 'bg-amber-400/10 text-amber-300',
                            };
                        @endphp

                        <tr class="align-top hover:bg-white/[0.02]">
                            <td class="px-5 py-4">
                                <p class="font-medium text-white">
                                    {{ $order->order_code ?? '#' . $order->id }}
                                </p>
                                <p class="mt-1 text-xs text-slate-600">
                                    ID {{ $order->id }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-slate-200">
                                    {{ $order->machine?->machine_code ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $order->machine?->name ?? 'Mesin tidak ditemukan' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                @forelse ($order->items as $item)
                                    <div class="mb-2 last:mb-0">
                                        <p class="text-slate-200">
                                            {{ $item->product?->name ?? 'Produk tidak ditemukan' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $item->quantity }} unit
                                            · Slot {{ $item->slot?->slot_code ?? '—' }}
                                        </p>
                                    </div>
                                @empty
                                    <span class="text-xs text-slate-500">
                                        Tidak ada detail item
                                    </span>
                                @endforelse
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-white">
                                Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase {{ $badgeClass }}">
                                    {{ $order->status ?? 'UNKNOWN' }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-slate-300">
                                    {{ $order->created_at?->format('d M Y') ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $order->created_at?->format('H:i:s') ?? '' }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="text-sm text-slate-300">
                                    Belum ada pesanan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Pesanan akan muncul setelah tercatat di database.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-white/[0.06] px-5 py-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
