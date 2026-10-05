@extends('layouts.dashboard')

@section('content')
<div
    x-data="slotManager()"
    class="space-y-6"
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
                <span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span>
                Operations
            </div>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                Stock & Slots
            </h1>

            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                Manage machine slots, products, capacity, and current stock.
            </p>
        </div>

        <button
            type="button"
            @click="openCreate()"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="h-4 w-4"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 4v16m8-8H4"
                />
            </svg>

            Add Slot
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-400/15 bg-emerald-400/[0.06] px-4 py-3">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/10 text-emerald-300">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="h-4 w-4"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m5 12 4 4L19 6"
                        />
                    </svg>
                </div>

                <div>
                    <p class="text-sm font-medium text-emerald-300">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-400/15 bg-rose-400/[0.06] px-4 py-3">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-400/10 text-rose-300">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="h-4 w-4"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </div>

                <div>
                    <p class="text-sm font-medium text-rose-300">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-amber-400/15 bg-amber-400/[0.06] px-4 py-3">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-400/10 text-amber-300">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="h-4 w-4"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.5m0 3.5h.01M10.3 4.8 2.9 17.5a2 2 0 0 0 1.73 3h14.74a2 2 0 0 0 1.73-3L13.7 4.8a2 2 0 0 0-3.4 0Z"
                        />
                    </svg>
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-medium text-amber-300">
                        Please check the form.
                    </p>

                    <ul class="mt-1 list-disc space-y-1 pl-4 text-xs text-slate-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-[#0b1220]">
        {{-- Card Header --}}
        <div class="flex flex-col gap-2 border-b border-white/[0.06] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-white">
                    Machine Slots
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    {{ $slots->count() }} slot{{ $slots->count() === 1 ? '' : 's' }} configured
                </p>
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Inventory overview
            </div>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-white/[0.05] bg-white/[0.015]">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Machine
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Slot
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Product
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Capacity
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Stock
                        </th>

                        <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/[0.04]">
                    @forelse ($slots as $slot)
                        @php
                            $stockPercentage = $slot->capacity > 0
                                ? ($slot->stock / $slot->capacity) * 100
                                : 0;
                        @endphp

                        <tr class="group transition hover:bg-white/[0.02]">
                            {{-- Machine --}}
                            <td class="px-5 py-4">
                                <div>
                                    <div class="text-sm font-medium text-white">
                                        {{ $slot->machine->name }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $slot->machine->machine_code }}
                                    </div>
                                </div>
                            </td>

                            {{-- Slot --}}
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-lg border border-white/[0.08] bg-white/[0.03] px-2.5 py-1 text-xs font-semibold text-slate-300">
                                    {{ $slot->slot_code }}
                                </span>
                            </td>

                            {{-- Product --}}
                            <td class="px-5 py-4">
                                <div>
                                    <div class="text-sm font-medium text-slate-200">
                                        {{ $slot->product->name }}
                                    </div>

                                    @if ($slot->product->category)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $slot->product->category->name }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Capacity --}}
                            <td class="px-5 py-4">
                                <span class="text-sm text-slate-300">
                                    {{ $slot->capacity }}
                                </span>
                            </td>

                            {{-- Stock --}}
                            <td class="px-5 py-4">
                                <div class="min-w-[150px]">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-semibold text-white">
                                            {{ $slot->stock }} / {{ $slot->capacity }}
                                        </span>

                                        @if ($slot->stock === 0)
                                            <span class="rounded-full border border-rose-400/15 bg-rose-400/[0.08] px-2 py-1 text-[10px] font-semibold text-rose-300">
                                                Empty
                                            </span>
                                        @elseif ($stockPercentage <= 30)
                                            <span class="rounded-full border border-amber-400/15 bg-amber-400/[0.08] px-2 py-1 text-[10px] font-semibold text-amber-300">
                                                Low
                                            </span>
                                        @else
                                            <span class="rounded-full border border-emerald-400/15 bg-emerald-400/[0.08] px-2 py-1 text-[10px] font-semibold text-emerald-300">
                                                Healthy
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/[0.06]">
                                        <div
                                            class="h-full rounded-full transition-all
                                                {{ $slot->stock === 0
                                                    ? 'bg-rose-400'
                                                    : ($stockPercentage <= 30
                                                        ? 'bg-amber-400'
                                                        : 'bg-emerald-400') }}"
                                            style="width: {{ min(100, max(0, $stockPercentage)) }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        @click='openEdit(@json($slot))'
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025] text-slate-400 transition hover:border-cyan-400/20 hover:bg-cyan-400/[0.06] hover:text-cyan-300"
                                        title="Edit slot"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            class="h-4 w-4"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m16.862 4.487 2.651 2.651M6.75 17.25l1.58-.263a2 2 0 0 0 1.02-.536L19.5 6.301a2.25 2.25 0 0 0-3.182-3.182L6.17 13.267a2 2 0 0 0-.537 1.02L5.37 15.87a1.5 1.5 0 0 0 1.38 1.38Z"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        @click='openDelete(@json($slot))'
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025] text-slate-400 transition hover:border-rose-400/20 hover:bg-rose-400/[0.06] hover:text-rose-300"
                                        title="Delete slot"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            class="h-4 w-4"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 7h12M10 11v5m4-5v5M9 7l.5-1.5A1 1 0 0 1 10.45 5h3.1a1 1 0 0 1 .95.5L15 7m-8 0 .6 12.1a1 1 0 0 0 1 .9h6.8a1 1 0 0 0 1-.9L17 7M9 7h6"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/[0.03] text-slate-600">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        class="h-6 w-6"
                                        stroke-width="1.7"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5v-9Z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m4 7.5 8 3.5 8-3.5M12 11v9"
                                        />
                                    </svg>
                                </div>

                                <p class="mt-4 text-sm font-medium text-slate-300">
                                    No machine slots yet
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Add your first machine slot to start managing stock.
                                </p>

                                <button
                                    type="button"
                                    @click="openCreate()"
                                    class="mt-4 inline-flex items-center gap-2 rounded-lg border border-cyan-400/15 bg-cyan-400/[0.06] px-3 py-2 text-xs font-semibold text-cyan-300 transition hover:bg-cyan-400/[0.1]"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        class="h-3.5 w-3.5"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 5v14m-7-7h14"
                                        />
                                    </svg>

                                    Add first slot
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="space-y-3 p-4 md:hidden">
            @forelse ($slots as $slot)
                @php
                    $stockPercentage = $slot->capacity > 0
                        ? ($slot->stock / $slot->capacity) * 100
                        : 0;
                @endphp

                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.015] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-lg border border-white/[0.08] bg-white/[0.03] px-2 py-1 text-[11px] font-semibold text-slate-300">
                                    {{ $slot->slot_code }}
                                </span>

                                @if ($slot->stock === 0)
                                    <span class="rounded-full border border-rose-400/15 bg-rose-400/[0.08] px-2 py-1 text-[10px] font-semibold text-rose-300">
                                        Empty
                                    </span>
                                @elseif ($stockPercentage <= 30)
                                    <span class="rounded-full border border-amber-400/15 bg-amber-400/[0.08] px-2 py-1 text-[10px] font-semibold text-amber-300">
                                        Low
                                    </span>
                                @else
                                    <span class="rounded-full border border-emerald-400/15 bg-emerald-400/[0.08] px-2 py-1 text-[10px] font-semibold text-emerald-300">
                                        Healthy
                                    </span>
                                @endif
                            </div>

                            <h3 class="mt-3 truncate text-sm font-semibold text-white">
                                {{ $slot->product->name }}
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $slot->machine->machine_code }}
                                ·
                                {{ $slot->machine->name }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                @click='openEdit(@json($slot))'
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025] text-slate-400 hover:text-cyan-300"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m16.862 4.487 2.651 2.651M6.75 17.25l1.58-.263a2 2 0 0 0 1.02-.536L19.5 6.301a2.25 2.25 0 0 0-3.182-3.182L6.17 13.267a2 2 0 0 0-.537 1.02L5.37 15.87a1.5 1.5 0 0 0 1.38 1.38Z"
                                    />
                                </svg>
                            </button>

                            <button
                                type="button"
                                @click='openDelete(@json($slot))'
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025] text-slate-400 hover:text-rose-300"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 7h12M10 11v5m4-5v5M9 7l.5-1.5A1 1 0 0 1 10.45 5h3.1a1 1 0 0 1 .95.5L15 7m-8 0 .6 12.1a1 1 0 0 0 1 .9h6.8a1 1 0 0 0 1-.9L17 7M9 7h6"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">
                                Current Stock
                            </span>

                            <span class="text-sm font-semibold text-white">
                                {{ $slot->stock }} / {{ $slot->capacity }}
                            </span>
                        </div>

                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/[0.06]">
                            <div
                                class="h-full rounded-full
                                    {{ $slot->stock === 0
                                        ? 'bg-rose-400'
                                        : ($stockPercentage <= 30
                                            ? 'bg-amber-400'
                                            : 'bg-emerald-400') }}"
                                style="width: {{ min(100, max(0, $stockPercentage)) }}%"
                            ></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <p class="text-sm text-slate-300">
                        No machine slots yet
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Add your first machine slot.
                    </p>

                    <button
                        type="button"
                        @click="openCreate()"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-cyan-400 px-3 py-2 text-xs font-semibold text-slate-950"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            class="h-3.5 w-3.5"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 5v14m-7-7h14"
                            />
                        </svg>

                        Add Slot
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Overlay --}}
    <div
        x-show="drawerOpen"
        x-transition.opacity
        class="fixed inset-0 z-[60] bg-slate-950/70 backdrop-blur-sm"
        style="display: none;"
        @click="closeDrawer()"
    ></div>

    {{-- Slide Over --}}
    <div
        x-show="drawerOpen"
        x-transition:enter="transition transform ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-[70] flex w-full max-w-md flex-col border-l border-white/[0.06] bg-[#0a1020] shadow-2xl"
        style="display: none;"
    >
        {{-- Drawer Header --}}
        <div class="flex items-start justify-between border-b border-white/[0.06] px-5 py-5">
            <div>
                <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-400">
                    Inventory
                </div>

                <h2
                    x-text="drawerTitle"
                    class="mt-1 text-lg font-semibold text-white"
                ></h2>

                <p class="mt-1 text-xs text-slate-500">
                    Configure machine slot and stock.
                </p>
            </div>

            <button
                type="button"
                @click="closeDrawer()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025] text-slate-400 transition hover:text-white"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    class="h-4 w-4"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 6l12 12M18 6 6 18"
                    />
                </svg>
            </button>
        </div>

        {{-- Drawer Body --}}
        <div class="flex-1 overflow-y-auto px-5 py-5">
            <form
                :action="formAction"
                method="POST"
                class="space-y-5"
            >
                @csrf
                <input
                    type="hidden"
                    name="_method"
                    :value="formMethod === 'POST' ? 'POST' : 'PUT'"
                >

                {{-- Machine --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-400">
                        Machine
                    </label>

                    <select
                        name="machine_id"
                        x-model="form.machine_id"
                        required
                        class="w-full rounded-xl border border-white/[0.08] bg-white/[0.025] px-3.5 py-3 text-sm text-white outline-none transition focus:border-cyan-400/30 focus:ring-2 focus:ring-cyan-400/10"
                    >
                        <option value="" class="bg-slate-900">
                            Select machine
                        </option>

                        @foreach ($machines as $machine)
                            <option
                                value="{{ $machine->id }}"
                                class="bg-slate-900"
                            >
                                {{ $machine->machine_code }} — {{ $machine->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Product --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-400">
                        Product
                    </label>

                    <select
                        name="product_id"
                        x-model="form.product_id"
                        required
                        class="w-full rounded-xl border border-white/[0.08] bg-white/[0.025] px-3.5 py-3 text-sm text-white outline-none transition focus:border-cyan-400/30 focus:ring-2 focus:ring-cyan-400/10"
                    >
                        <option value="" class="bg-slate-900">
                            Select product
                        </option>

                        @foreach ($products as $product)
                            <option
                                value="{{ $product->id }}"
                                class="bg-slate-900"
                            >
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Slot Code --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-400">
                        Slot Code
                    </label>

                    <input
                        type="text"
                        name="slot_code"
                        x-model="form.slot_code"
                        required
                        maxlength="20"
                        placeholder="A01"
                        class="w-full rounded-xl border border-white/[0.08] bg-white/[0.025] px-3.5 py-3 text-sm text-white placeholder-slate-700 outline-none transition focus:border-cyan-400/30 focus:ring-2 focus:ring-cyan-400/10"
                    >

                    <p class="mt-1.5 text-[11px] text-slate-600">
                        Slot code must be unique within the selected machine.
                    </p>
                </div>

                {{-- Capacity --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-400">
                        Capacity
                    </label>

                    <input
                        type="number"
                        name="capacity"
                        x-model="form.capacity"
                        min="1"
                        required
                        placeholder="10"
                        class="w-full rounded-xl border border-white/[0.08] bg-white/[0.025] px-3.5 py-3 text-sm text-white placeholder-slate-700 outline-none transition focus:border-cyan-400/30 focus:ring-2 focus:ring-cyan-400/10"
                    >
                </div>

                {{-- Stock --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-400">
                        Current Stock
                    </label>

                    <input
                        type="number"
                        name="stock"
                        x-model="form.stock"
                        min="0"
                        required
                        placeholder="0"
                        class="w-full rounded-xl border border-white/[0.08] bg-white/[0.025] px-3.5 py-3 text-sm text-white placeholder-slate-700 outline-none transition focus:border-cyan-400/30 focus:ring-2 focus:ring-cyan-400/10"
                    >

                    <p class="mt-1.5 text-[11px] text-slate-600">
                        Stock cannot be greater than capacity.
                    </p>
                </div>

                {{-- Stock Preview --}}
                <div class="rounded-2xl border border-cyan-400/10 bg-cyan-400/[0.04] p-4">
                    <div class="flex items-center gap-2 text-xs font-medium text-cyan-300">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            class="h-4 w-4"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 6.5 12 3l8 3.5v9L12 19l-8-3.5v-9Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 8.5 12 10l4-1.5"
                            />
                        </svg>

                        Stock Preview
                    </div>

                    <div class="mt-3 flex items-end justify-between">
                        <div>
                            <div
                                x-text="`${form.stock || 0} / ${form.capacity || 0}`"
                                class="text-2xl font-semibold tracking-tight text-white"
                            ></div>

                            <div class="mt-1 text-[11px] text-slate-500">
                                Current inventory
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex gap-3 border-t border-white/[0.06] pt-5">
                    <button
                        type="button"
                        @click="closeDrawer()"
                        class="flex-1 rounded-xl border border-white/[0.08] bg-white/[0.025] px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-white/[0.05] hover:text-white"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="flex-1 rounded-xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                        x-text="submitLabel"
                    ></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation --}}
    <div
        x-show="deleteOpen"
        x-transition.opacity
        class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/75 px-4 backdrop-blur-sm"
        style="display: none;"
        @keydown.escape.window="deleteOpen = false"
    >
        <div
            x-show="deleteOpen"
            x-transition.scale
            @click.outside="deleteOpen = false"
            class="w-full max-w-md rounded-2xl border border-white/[0.08] bg-[#0b1220] p-5 shadow-2xl"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-400/10 text-rose-300">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="h-5 w-5"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.5m0 3.5h.01M10.3 4.8 2.9 17.5a2 2 0 0 0 1.73 3h14.74a2 2 0 0 0 1.73-3L13.7 4.8a2 2 0 0 0-3.4 0Z"
                        />
                    </svg>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-white">
                        Delete Slot
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Are you sure you want to delete slot
                        <span
                            x-text="deleteSlotCode"
                            class="font-semibold text-slate-300"
                        ></span>?
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button
                    type="button"
                    @click="deleteOpen = false"
                    class="flex-1 rounded-xl border border-white/[0.08] bg-white/[0.025] px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-white/[0.05] hover:text-white"
                >
                    Cancel
                </button>

                <form
                    :action="deleteAction"
                    method="POST"
                    class="flex-1"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-rose-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-rose-300"
                    >
                        Delete Slot
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('slotManager', () => ({
        drawerOpen: false,
        deleteOpen: false,

        drawerTitle: 'Add Slot',
        submitLabel: 'Save Slot',

        formAction: '{{ route('stock-slots.store') }}',
        formMethod: 'POST',

        deleteAction: '',
        deleteSlotCode: '',

        form: {
            machine_id: '',
            product_id: '',
            slot_code: '',
            capacity: '',
            stock: '',
        },

        openCreate() {
            this.drawerTitle = 'Add Slot';
            this.submitLabel = 'Save Slot';

            this.formAction = '{{ route('stock-slots.store') }}';
            this.formMethod = 'POST';

            this.form = {
                machine_id: '',
                product_id: '',
                slot_code: '',
                capacity: '',
                stock: '',
            };

            this.drawerOpen = true;
        },

        openEdit(slot) {
            this.drawerTitle = 'Edit Slot';
            this.submitLabel = 'Update Slot';

            this.formAction = `/stock-slots/${slot.id}`;
            this.formMethod = 'PUT';

            this.form = {
                machine_id: slot.machine_id ?? '',
                product_id: slot.product_id ?? '',
                slot_code: slot.slot_code ?? '',
                capacity: slot.capacity ?? '',
                stock: slot.stock ?? '',
            };

            this.drawerOpen = true;
        },

        openDelete(slot) {
            this.deleteSlotCode = slot.slot_code;
            this.deleteAction = `/stock-slots/${slot.id}`;
            this.deleteOpen = true;
        },

        closeDrawer() {
            this.drawerOpen = false;
        },
    }));
});
</script>
@endsection