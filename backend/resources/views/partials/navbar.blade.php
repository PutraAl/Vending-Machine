<header class="glass sticky top-0 z-30 border-b border-white/[0.06]">

    <div class="flex min-h-[73px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">

        {{-- Left --}}
        <div class="flex min-w-0 items-center gap-3">

            {{-- Mobile menu --}}
            <button id="open-mobile-sidebar" type="button" aria-label="Open navigation" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
           border border-white/[0.06]
           bg-white/[0.025]
           text-slate-400
           transition
           hover:border-white/[0.1]
           hover:text-white
           lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    class="h-5 w-5" stroke-width="1.8">
                    <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="min-w-0">

                <p class="truncate text-[9px] font-semibold uppercase tracking-[0.2em] text-cyan-400 sm:text-[10px]">
                    IoT Control Center
                </p>

                <h1 class="truncate text-base font-semibold tracking-tight text-white sm:text-lg">
                    @yield('title', 'Dashboard')
                </h1>

            </div>

        </div>


        {{-- Right --}}
        <div class="flex shrink-0 items-center gap-2">

            {{-- Connection --}}
            <div
                class="hidden items-center gap-2 rounded-xl border border-emerald-400/10 bg-emerald-400/[0.04] px-3 py-2 md:flex">

                <span class="status-pulse h-1.5 w-1.5 rounded-full bg-emerald-400"></span>

                <span class="text-[11px] font-medium text-emerald-300">
                    Backend connected
                </span>

            </div>


            {{-- Notification --}}
            <button type="button"
                class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-white/[0.06] bg-white/[0.025] text-slate-500 transition hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    class="h-4 w-4" stroke-width="1.8">
                    <path stroke-linecap="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                    <path stroke-linecap="round" d="M10 21h4" />
                </svg>

                <span class="absolute right-2.5 top-2.5 h-1.5 w-1.5 rounded-full bg-cyan-400"></span>
            </button>


            {{-- Desktop user --}}
            <div class="ml-1 hidden items-center gap-3 border-l border-white/[0.06] pl-3 sm:flex">

                <div
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-cyan-400/10 text-sm font-semibold text-cyan-300">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>

                <div class="max-w-[100px]">
                    <p class="truncate text-sm font-medium text-white">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="mt-0.5 text-[11px] capitalize text-slate-600">
                        {{ auth()->user()->role }}
                    </p>
                </div>

            </div>


            {{-- Mobile avatar --}}
            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan-400/10 text-sm font-semibold text-cyan-300 sm:hidden">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

        </div>

    </div>

</header>