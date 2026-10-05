<aside
    id="mobile-sidebar"
    class="fixed inset-y-0 left-0 z-50
           w-[min(300px,86vw)]
           -translate-x-full
           border-r border-white/[0.06]
           bg-[#080d17]
           shadow-2xl shadow-black/40
           transition-transform duration-300 ease-out
           lg:sticky lg:top-0
           lg:z-30 lg:flex lg:h-screen
           lg:w-[270px]
           lg:translate-x-0
           lg:flex-col lg:shadow-none"
>
    {{-- =========================================================
        BRAND + MOBILE CLOSE
    ========================================================== --}}
    <div class="flex h-[73px] shrink-0 items-center justify-between border-b border-white/[0.06] px-5">

        <div class="flex min-w-0 items-center gap-3">

            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-400/[0.08] ring-1 ring-cyan-400/15">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    class="h-5 w-5 text-cyan-300"
                    stroke-width="1.7"
                >
                    <rect x="5" y="3" width="14" height="18" rx="2"/>
                    <path stroke-linecap="round" d="M8 7h8M8 11h8M8 15h4"/>
                </svg>
            </div>

            <div class="min-w-0">
                <div class="truncate text-sm font-bold tracking-[0.18em] text-white">
                    VENDING<span class="text-cyan-400">PANAS</span>
                </div>

                <div class="mt-0.5 truncate text-[10px] uppercase tracking-[0.18em] text-slate-600">
                    IoT Control Center
                </div>
            </div>

        </div>

        {{-- Mobile close --}}
        <button
            id="close-mobile-sidebar"
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                   text-slate-500 transition hover:bg-white/[0.05] hover:text-white
                   lg:hidden"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="h-5 w-5"
                stroke-width="1.8"
            >
                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
            </svg>
        </button>

    </div>


    {{-- =========================================================
        NAVIGATION
    ========================================================== --}}
    <nav class="flex-1 overflow-y-auto px-3 py-5">

        {{-- ==================== OVERVIEW ==================== --}}
        <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-600">
            Overview
        </p>

        @php
            $dashboardActive = request()->is('dashboard');
        @endphp

        <div class="mt-2">
            <a
                href="{{ url('/dashboard') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition
                    {{ $dashboardActive
                        ? 'bg-cyan-400/[0.08] text-cyan-300 ring-1 ring-cyan-400/15'
                        : 'text-slate-500 hover:bg-white/[0.03] hover:text-white' }}"
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg transition
                        {{ $dashboardActive
                            ? 'bg-cyan-400/10 text-cyan-300'
                            : 'bg-white/[0.025] text-slate-500 group-hover:text-white' }}"
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
                            d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10"
                        />
                    </svg>
                </span>

                Dashboard
            </a>
        </div>


        {{-- ==================== OPERATIONS ==================== --}}
        <p class="mt-7 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-600">
            Operations
        </p>

        @php
            $menuItems = [
                [
                    'label' => 'Products',
                    'href' => '/products',
                    'pattern' => 'products*',
                    'icon' => 'box',
                ],
                [
                    'label' => 'Categories',
                    'href' => '/categories',
                    'pattern' => 'categories*',
                    'icon' => 'list',
                ],
                [
                    'label' => 'Machines',
                    'href' => '/machines',
                    'pattern' => 'machines*',
                    'icon' => 'machine',
                ],
                [
                    'label' => 'Stock & Slots',
                    'href' => '/stock-slots',
                    'pattern' => 'stock-slots*',
                    'icon' => 'stock',
                ],
                [
                    'label' => 'Orders',
                    'href' => '/orders',
                    'pattern' => 'orders*',
                    'icon' => 'orders',
                ],
                [
                    'label' => 'Dispensing',
                    'href' => '/dispensing',
                    'pattern' => 'dispensing*',
                    'icon' => 'dispense',
                ],
            ];
        @endphp

        <div class="mt-2 space-y-1">

            @foreach ($menuItems as $item)

                @php
                    $isActive = request()->is($item['pattern']);
                @endphp

                <a
                    href="{{ url($item['href']) }}"
                    class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition
                        {{ $isActive
                            ? 'bg-cyan-400/[0.08] text-cyan-300 ring-1 ring-cyan-400/15'
                            : 'text-slate-500 hover:bg-white/[0.03] hover:text-white' }}"
                >

                    {{-- Icon --}}
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg transition
                            {{ $isActive
                                ? 'bg-cyan-400/10 text-cyan-300'
                                : 'bg-white/[0.025] text-slate-500 group-hover:text-white' }}"
                    >

                        @if ($item['icon'] === 'box')

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                class="h-4 w-4"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" d="M4 6h16v12H4z"/>
                                <path stroke-linecap="round" d="M8 10h8M8 14h5"/>
                            </svg>

                        @elseif ($item['icon'] === 'list')

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                class="h-4 w-4"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" d="M5 6h14M5 12h14M5 18h9"/>
                            </svg>

                        @elseif ($item['icon'] === 'machine')

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                class="h-4 w-4"
                                stroke-width="1.8"
                            >
                                <rect x="5" y="3" width="14" height="18" rx="2"/>
                                <path stroke-linecap="round" d="M8 8h8M8 12h8M8 16h4"/>
                            </svg>

                        @elseif ($item['icon'] === 'stock')

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                class="h-4 w-4"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                            </svg>

                        @elseif ($item['icon'] === 'orders')

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                class="h-4 w-4"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" d="M6 4h12v16H6z"/>
                                <path stroke-linecap="round" d="M9 8h6M9 12h6M9 16h4"/>
                            </svg>

                        @else

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                class="h-4 w-4"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" d="M12 3v18M5 8h14M5 16h14"/>
                            </svg>

                        @endif

                    </span>

                    {{ $item['label'] }}

                </a>

            @endforeach

        </div>


        {{-- ==================== MONITORING ==================== --}}
        <p class="mt-7 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-600">
            Monitoring
        </p>

        @php
            $monitoringItems = [
                [
                    'label' => 'Telemetry',
                    'href' => '/telemetry',
                    'pattern' => 'telemetry*',
                    'icon' => 'telemetry',
                ],
                [
                    'label' => 'Machine Errors',
                    'href' => '/machine-errors',
                    'pattern' => 'machine-errors*',
                    'icon' => 'errors',
                ],
            ];
        @endphp

        <div class="mt-2 space-y-1">

            @foreach ($monitoringItems as $item)

                @php
                    $isActive = request()->is($item['pattern']);
                @endphp

                <a
                    href="{{ url($item['href']) }}"
                    class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition
                        {{ $isActive
                            ? 'bg-cyan-400/[0.08] text-cyan-300 ring-1 ring-cyan-400/15'
                            : 'text-slate-500 hover:bg-white/[0.03] hover:text-white' }}"
                >

                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg transition
                            {{ $isActive
                                ? 'bg-cyan-400/10 text-cyan-300'
                                : 'bg-white/[0.025] text-slate-500 group-hover:text-white' }}"
                    >

                        @if ($item['icon'] === 'telemetry')

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
                                    d="M3 12h4l2-7 5 14 2-7h5"
                                />
                            </svg>

                        @else

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                class="h-4 w-4"
                                stroke-width="1.8"
                            >
                                <circle cx="12" cy="12" r="8.5"/>
                                <path stroke-linecap="round" d="M12 8v4l3 2"/>
                            </svg>

                        @endif

                    </span>

                    {{ $item['label'] }}

                </a>

            @endforeach

        </div>

    </nav>


    {{-- =========================================================
        USER
    ========================================================== --}}
    <div class="border-t border-white/[0.06] p-3">

        <div class="flex items-center gap-3 rounded-2xl bg-white/[0.025] p-3">

            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-400/10 text-sm font-semibold text-cyan-300">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div class="min-w-0 flex-1">

                <p class="truncate text-sm font-medium text-white">
                    {{ auth()->user()->name }}
                </p>

                <p class="mt-0.5 text-[11px] capitalize text-slate-600">
                    {{ auth()->user()->role }}
                </p>

            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition hover:bg-red-400/10 hover:text-red-300"
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
                            d="M10 17l5-5-5-5M15 12H3M21 4v16"
                        />
                    </svg>
                </button>
            </form>

        </div>

    </div>

</aside>