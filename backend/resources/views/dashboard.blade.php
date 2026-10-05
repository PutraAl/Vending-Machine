@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')

    {{-- Hero --}}
    <section class="mb-7">

        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">

            <div>

                <div class="flex items-center gap-2 text-[11px] text-slate-600">
                    <span>VendingOS</span>
                    <span>/</span>
                    <span class="text-slate-400">Dashboard</span>
                </div>

                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                    Good morning, {{ auth()->user()->name }}.
                </h2>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Monitor machines, telemetry, stock, orders, and dispensing activity
                    from one central control center.
                </p>

            </div>


            {{-- System status --}}
            <div class="flex items-center gap-2 rounded-full border border-emerald-400/10 bg-emerald-400/[0.04] px-3 py-2">

                <span class="status-pulse h-2 w-2 rounded-full bg-emerald-400"></span>

                <span class="text-xs font-medium text-emerald-300">
                    System Operational
                </span>

            </div>

        </div>

    </section>


    {{-- Statistic Cards --}}
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

        {{-- Products --}}
        <div class="panel panel-hover rounded-2xl p-5">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Total Products
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-white">
                        {{ number_format($totalProducts) }}
                    </p>

                    <p class="mt-2 text-[11px] text-slate-600">
                        Registered in catalog
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400/[0.08] text-cyan-300">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        class="h-5 w-5" stroke-width="1.8">
                        <path stroke-linecap="round" d="M4 6h16v12H4z" />
                        <path stroke-linecap="round" d="M8 10h8M8 14h5" />
                    </svg>

                </div>

            </div>

        </div>


        {{-- Machines --}}
        <div class="panel panel-hover rounded-2xl p-5">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Machines
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-white">
                        {{ number_format($totalMachines) }}
                    </p>

                    <p class="mt-2 text-[11px] text-slate-600">
                        Registered machines
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400/[0.08] text-emerald-300">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        class="h-5 w-5" stroke-width="1.8">
                        <rect x="5" y="3" width="14" height="18" rx="2" />
                        <path stroke-linecap="round" d="M8 8h8M8 12h8M8 16h4" />
                    </svg>

                </div>

            </div>

        </div>


        {{-- Orders --}}
        <div class="panel panel-hover rounded-2xl p-5">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Orders Today
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-white">
                        {{ number_format($ordersToday) }}
                    </p>

                    <p class="mt-2 text-[11px] text-slate-600">
                        Completed and active
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-400/[0.08] text-violet-300">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        class="h-5 w-5" stroke-width="1.8">
                        <path stroke-linecap="round" d="M6 4h12v16H6z" />
                        <path stroke-linecap="round" d="M9 8h6M9 12h6M9 16h4" />
                    </svg>

                </div>

            </div>

        </div>


        {{-- Active Dispensing --}}
        <div class="panel panel-hover rounded-2xl p-5">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Active Dispensing
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-white">
                        {{ number_format($activeDispensing) }}
                    </p>

                    <p class="mt-2 text-[11px] text-slate-600">
                        Machines currently dispensing
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400/[0.08] text-amber-300">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        class="h-5 w-5" stroke-width="1.8">
                        <path stroke-linecap="round" d="M12 3v18M5 8h14M5 16h14" />
                    </svg>

                </div>

            </div>

        </div>

    </section>


    {{-- Main monitoring area --}}
    <section class="mt-6 grid gap-6 xl:grid-cols-[1.7fr_1fr]">

        {{-- Machine Overview --}}
        <div class="panel rounded-2xl">

            <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-5">

                <div>

                    <div class="flex items-center gap-2">

                        <span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span>

                        <h3 class="text-sm font-semibold text-white">
                            Machine Overview
                        </h3>

                    </div>

                    <p class="mt-1 text-xs text-slate-600">
                        Current machine health and operating state.
                    </p>

                </div>

                <a href="#" class="text-xs font-medium text-cyan-300 hover:text-cyan-200">
                    View all
                </a>

            </div>


            <div class="grid gap-4 p-5 md:grid-cols-2">

                <div class="space-y-3">
                    @forelse ($machines as $machine)
                        @php
                            $telemetry = $machine->latestTelemetry;
                        @endphp

                        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                                {{-- Machine identity --}}
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-400/[0.08] text-cyan-300 ring-1 ring-cyan-400/15">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" class="h-5 w-5" stroke-width="1.7">
                                                <rect x="5" y="3" width="14" height="18" rx="2" />
                                                <path stroke-linecap="round" d="M8 7h8M8 11h8M8 15h4" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-white">
                                                {{ $machine->machine_code }}
                                            </div>

                                            <div class="truncate text-xs text-slate-500">
                                                {{ $machine->name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status --}}
                                <div>
                                    @php
                                        $status = strtoupper($machine->status ?? 'OFFLINE');

                                        $statusClass = match ($status) {
                                            'ONLINE' => 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/20',
                                            'OFFLINE' => 'bg-rose-400/10 text-rose-300 ring-rose-400/20',
                                            default => 'bg-amber-400/10 text-amber-300 ring-amber-400/20',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] ring-1 {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </div>

                            </div>

                            {{-- Telemetry --}}
                            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-white/[0.06] pt-4 sm:grid-cols-3">

                                <div>
                                    <div class="text-[10px] uppercase tracking-[0.14em] text-slate-600">
                                        State
                                    </div>

                                    <div class="mt-1 text-sm font-semibold text-white">
                                        {{ $telemetry?->state ?? 'UNKNOWN' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-[10px] uppercase tracking-[0.14em] text-slate-600">
                                        Temperature
                                    </div>

                                    <div class="mt-1 text-sm font-semibold text-white">
                                        {{ $telemetry?->temperature !== null ? number_format((float) $telemetry->temperature, 1) . ' °C' : '-- °C' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-[10px] uppercase tracking-[0.14em] text-slate-600">
                                        Last Telemetry
                                    </div>

                                    <div class="mt-1 text-sm font-semibold text-white">
                                        {{ $telemetry?->created_at?->format('H:i:s') ?? '—' }}
                                    </div>
                                </div>

                            </div>
                        </div>

                    @empty

                        <div class="rounded-2xl border border-dashed border-white/[0.08] bg-white/[0.015] p-8 text-center">
                            <div class="text-sm font-medium text-slate-400">
                                Belum ada mesin.
                            </div>

                            <div class="mt-1 text-xs text-slate-600">
                                Data mesin akan muncul di sini setelah ditambahkan.
                            </div>
                        </div>

                    @endforelse
                </div>



            </div>

        </div>


        {{-- System Health --}}
        <div class="panel rounded-2xl">

            <div class="border-b border-white/[0.06] px-5 py-5">

                <div class="flex items-center gap-2">

                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span>

                    <h3 class="text-sm font-semibold text-white">
                        System Health
                    </h3>

                </div>

                <p class="mt-1 text-xs text-slate-600">
                    Infrastructure and machine connectivity.
                </p>

            </div>


            <div class="space-y-3 p-5">

                {{-- Backend --}}
                <div
                    class="flex items-center justify-between rounded-xl border border-white/[0.05] bg-white/[0.02] px-4 py-3">

                    <div class="flex items-center gap-3">

                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-400/[0.07] text-cyan-300">
                            <span class="text-xs">01</span>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-slate-300">
                                Laravel Backend
                            </p>

                            <p class="mt-0.5 text-[10px] text-slate-600">
                                Application service
                            </p>
                        </div>

                    </div>

                    <span class="text-[10px] font-semibold text-emerald-300">
                        ONLINE
                    </span>

                </div>


                {{-- Database --}}
                <div
                    class="flex items-center justify-between rounded-xl border border-white/[0.05] bg-white/[0.02] px-4 py-3">

                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-400/[0.07] text-violet-300">
                            <span class="text-xs">02</span>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-slate-300">
                                PostgreSQL
                            </p>

                            <p class="mt-0.5 text-[10px] text-slate-600">
                                Primary database
                            </p>
                        </div>

                    </div>

                    <span class="text-[10px] font-semibold text-emerald-300">
                        ONLINE
                    </span>

                </div>


                {{-- MQTT --}}
                <div
                    class="flex items-center justify-between rounded-xl border border-white/[0.05] bg-white/[0.02] px-4 py-3">

                    <div class="flex items-center gap-3">

                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-400/[0.07] text-amber-300">
                            <span class="text-xs">03</span>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-slate-300">
                                MQTT Broker
                            </p>

                            <p class="mt-0.5 text-[10px] text-slate-600">
                                Machine communication
                            </p>
                        </div>

                    </div>

                    <span class="text-[10px] font-semibold text-amber-300">
                        READY
                    </span>

                </div>

            </div>

        </div>

    </section>


    {{-- Bottom --}}
    <section class="mt-6 grid gap-6 xl:grid-cols-[1.25fr_1fr]">

        {{-- Activity --}}
        <div class="panel rounded-2xl">

            <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-5">

                <div>

                    <h3 class="text-sm font-semibold text-white">
                        Recent Activity
                    </h3>

                    <p class="mt-1 text-xs text-slate-600">
                        Latest events from the system.
                    </p>

                </div>

                <a href="#" class="text-xs font-medium text-cyan-300">
                    View logs
                </a>

            </div>


            <div class="divide-y divide-white/[0.05]">

                <div class="flex items-start gap-4 px-5 py-4">

                    <div
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/[0.06] text-emerald-300">
                        ✓
                    </div>

                    <div class="min-w-0 flex-1">

                        <p class="text-xs font-medium text-slate-300">
                            Machine VM-001 is online
                        </p>

                        <p class="mt-1 text-[10px] text-slate-600">
                            System event
                        </p>

                    </div>

                    <span class="text-[10px] text-slate-700">
                        just now
                    </span>

                </div>


                <div class="flex items-start gap-4 px-5 py-4">

                    <div
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-400/[0.06] text-cyan-300">
                        ↑
                    </div>

                    <div class="min-w-0 flex-1">

                        <p class="text-xs font-medium text-slate-300">
                            Telemetry channel ready
                        </p>

                        <p class="mt-1 text-[10px] text-slate-600">
                            MQTT
                        </p>

                    </div>

                    <span class="text-[10px] text-slate-700">
                        —
                    </span>

                </div>


                <div class="flex items-start gap-4 px-5 py-4">

                    <div
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-400/[0.06] text-amber-300">
                        !
                    </div>

                    <div class="min-w-0 flex-1">

                        <p class="text-xs font-medium text-slate-300">
                            No active machine errors
                        </p>

                        <p class="mt-1 text-[10px] text-slate-600">
                            Monitoring
                        </p>

                    </div>

                    <span class="text-[10px] text-slate-700">
                        —
                    </span>

                </div>

            </div>

        </div>


        {{-- Stock --}}
        <div class="panel rounded-2xl">

            <div class="border-b border-white/[0.06] px-5 py-5">

                <div class="flex items-center justify-between">

                    <div>

                        <h3 class="text-sm font-semibold text-white">
                            Stock Alerts
                        </h3>

                        <p class="mt-1 text-xs text-slate-600">
                            Slots that need replenishment.
                        </p>

                    </div>

                    <a href="#" class="text-xs font-medium text-cyan-300">
                        View stock
                    </a>

                </div>

            </div>


            <div class="space-y-3 p-5">

                <div class="rounded-xl border border-red-400/10 bg-red-400/[0.03] p-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs font-semibold text-slate-200">
                                A02 · Mie Goreng
                            </p>

                            <p class="mt-1 text-[10px] text-slate-600">
                                Machine VM-001
                            </p>

                        </div>

                        <div class="text-right">

                            <p class="text-sm font-semibold text-red-300">
                                1
                            </p>

                            <p class="text-[9px] uppercase tracking-wider text-slate-600">
                                remaining
                            </p>

                        </div>

                    </div>

                </div>


                <div class="rounded-xl border border-amber-400/10 bg-amber-400/[0.03] p-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs font-semibold text-slate-200">
                                A01 · Nasi Goreng
                            </p>

                            <p class="mt-1 text-[10px] text-slate-600">
                                Machine VM-001
                            </p>

                        </div>

                        <div class="text-right">

                            <p class="text-sm font-semibold text-amber-300">
                                2
                            </p>

                            <p class="text-[9px] uppercase tracking-wider text-slate-600">
                                remaining
                            </p>

                        </div>

                    </div>

                </div>


                <div class="rounded-xl border border-cyan-400/10 bg-cyan-400/[0.03] p-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs font-semibold text-slate-200">
                                A03 · Ayam Crispy
                            </p>

                            <p class="mt-1 text-[10px] text-slate-600">
                                Machine VM-001
                            </p>

                        </div>

                        <div class="text-right">

                            <p class="text-sm font-semibold text-cyan-300">
                                4
                            </p>

                            <p class="text-[9px] uppercase tracking-wider text-slate-600">
                                remaining
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

@endsection