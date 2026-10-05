@extends('layouts.dashboard')

@section('title', 'Machines')

@section('content')

<div
    x-data="machineManager()"
    x-cloak
    class="space-y-6"
>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <div class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
                Machine Management
            </div>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                Machines
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Kelola mesin vending yang terhubung dengan sistem.
            </p>
        </div>

        <button
            type="button"
            @click="openCreate()"
            class="inline-flex items-center justify-center gap-2 rounded-xl
                   bg-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950
                   transition hover:bg-cyan-300"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="h-4 w-4"
                stroke-width="2"
            >
                <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
            </svg>

            Add Machine
        </button>

    </div>


    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-xl border border-emerald-400/15 bg-emerald-400/[0.06] px-4 py-3 text-sm text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-rose-400/15 bg-rose-400/[0.06] px-4 py-3 text-sm text-rose-300">
            {{ session('error') }}
        </div>
    @endif


    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-[#0b111c]">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="border-b border-white/[0.06] bg-white/[0.02]">

                    <tr class="text-left text-xs uppercase tracking-[0.12em] text-slate-500">

                        <th class="px-6 py-4">
                            Machine
                        </th>

                        <th class="px-6 py-4">
                            Location
                        </th>

                        <th class="px-6 py-4">
                            Status
                        </th>

                        <th class="px-6 py-4">
                            Temp Threshold
                        </th>

                        <th class="px-6 py-4">
                            Slots
                        </th>

                        <th class="px-6 py-4 text-right">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-white/[0.05]">

                    @forelse ($machines as $machine)

                        @php
                            $status = strtoupper($machine->status ?? 'OFFLINE');

                            $statusClass = match ($status) {
                                'ONLINE' => 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/15',
                                'OFFLINE' => 'bg-rose-400/10 text-rose-300 ring-rose-400/15',
                                default => 'bg-amber-400/10 text-amber-300 ring-amber-400/15',
                            };
                        @endphp

                        <tr class="transition hover:bg-white/[0.02]">

                            {{-- Machine --}}
                            <td class="px-6 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                                                bg-cyan-400/[0.08] text-cyan-300 ring-1 ring-cyan-400/10">

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            class="h-5 w-5"
                                            stroke-width="1.6"
                                        >
                                            <rect x="5" y="3" width="14" height="18" rx="2"/>
                                            <path stroke-linecap="round" d="M8 8h8M8 12h8M8 16h4"/>
                                        </svg>

                                    </div>

                                    <div class="min-w-0">

                                        <div class="font-semibold text-white">
                                            {{ $machine->machine_code }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-600">
                                            {{ $machine->name }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- Location --}}
                            <td class="px-6 py-4 text-slate-400">
                                {{ $machine->location ?: '—' }}
                            </td>


                            {{-- Status --}}
                            <td class="px-6 py-4">

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full
                                           px-2.5 py-1 text-[11px] font-semibold uppercase
                                           tracking-[0.08em] ring-1 {{ $statusClass }}"
                                >

                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                                    {{ $status }}

                                </span>

                            </td>


                            {{-- Temperature --}}
                            <td class="px-6 py-4">

                                <span class="font-medium text-slate-300">
                                    {{ number_format((float) $machine->temperature_threshold, 1) }} °C
                                </span>

                            </td>


                            {{-- Slots --}}
                            <td class="px-6 py-4">

                                <span class="inline-flex rounded-lg bg-white/[0.04] px-2.5 py-1 text-xs text-slate-300">
                                    {{ $machine->slots_count }}
                                </span>

                            </td>


                            {{-- Actions --}}
                            <td class="px-6 py-4">

                                <div class="flex justify-end gap-2">

                                    <button
                                        type="button"
                                        @click='openEdit(@json($machine))'
                                        class="rounded-lg px-3 py-2 text-xs font-medium text-slate-300
                                               transition hover:bg-white/[0.05] hover:text-white"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        @click='openDelete(@json($machine))'
                                        class="rounded-lg px-3 py-2 text-xs font-medium text-rose-400
                                               transition hover:bg-rose-400/[0.08]"
                                    >
                                        Delete
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="px-6 py-16 text-center">

                                <div class="text-sm font-medium text-slate-400">
                                    Belum ada machine.
                                </div>

                                <div class="mt-1 text-xs text-slate-600">
                                    Tambahkan machine pertama untuk mulai mengatur vending machine.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
        MACHINE DRAWER
    ========================================================== --}}
    <div
        x-show="drawerOpen"
        class="fixed inset-0 z-[70]"
        @keydown.escape.window="closeDrawer()"
    >

        <div
            class="absolute inset-0 bg-black/60"
            @click="closeDrawer()"
        ></div>


        <aside
            x-show="drawerOpen"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col
                   border-l border-white/[0.06] bg-[#080d17] shadow-2xl"
        >

            {{-- Header --}}
            <div class="flex h-[73px] shrink-0 items-center justify-between border-b border-white/[0.06] px-6">

                <div>

                    <div
                        class="text-sm font-semibold text-white"
                        x-text="drawerTitle"
                    ></div>

                    <div class="mt-1 text-xs text-slate-600">
                        Kelola data machine
                    </div>

                </div>

                <button
                    type="button"
                    @click="closeDrawer()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg
                           text-slate-500 transition hover:bg-white/[0.05] hover:text-white"
                >
                    ✕
                </button>

            </div>


            {{-- Body --}}
            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">

                <form
                    method="POST"
                    :action="formAction"
                    class="space-y-5"
                >

                    @csrf

                    <input
                        type="hidden"
                        name="_method"
                        :value="formMethod"
                    >


                    {{-- Machine Code --}}
                    <div>

                        <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                            Machine Code
                        </label>

                        <input
                            type="text"
                            name="machine_code"
                            x-model="form.machine_code"
                            required
                            class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                   px-4 py-3 text-sm uppercase text-white outline-none
                                   placeholder:text-slate-700
                                   focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                            placeholder="VM-001"
                        >

                    </div>


                    {{-- Name --}}
                    <div>

                        <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                            Machine Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            x-model="form.name"
                            required
                            class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                   px-4 py-3 text-sm text-white outline-none
                                   placeholder:text-slate-700
                                   focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                            placeholder="Vending Machine 01"
                        >

                    </div>


                    {{-- Location --}}
                    <div>

                        <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            x-model="form.location"
                            class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                   px-4 py-3 text-sm text-white outline-none
                                   placeholder:text-slate-700
                                   focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                            placeholder="Gedung A - Lantai 2"
                        >

                    </div>


                    {{-- Status --}}
                    <div>

                        <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                            Status
                        </label>

                        <select
                            name="status"
                            x-model="form.status"
                            required
                            class="w-full rounded-xl border border-white/[0.08] bg-[#0e1520]
                                   px-4 py-3 text-sm text-white outline-none
                                   focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                        >
                            <option value="ONLINE">ONLINE</option>
                            <option value="OFFLINE">OFFLINE</option>
                        </select>

                    </div>


                    {{-- Temperature Threshold --}}
                    <div>

                        <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                            Temperature Threshold
                        </label>

                        <div class="relative">

                            <input
                                type="number"
                                name="temperature_threshold"
                                x-model="form.temperature_threshold"
                                required
                                min="0"
                                step="0.1"
                                class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                       px-4 py-3 pr-14 text-sm text-white outline-none
                                       focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                                placeholder="80"
                            >

                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-slate-600">
                                °C
                            </span>

                        </div>

                        <p class="mt-1.5 text-[11px] text-slate-600">
                            Batas suhu yang digunakan untuk mendeteksi kondisi abnormal mesin.
                        </p>

                    </div>


                    {{-- Actions --}}
                    <div class="flex gap-3 border-t border-white/[0.06] pt-6">

                        <button
                            type="button"
                            @click="closeDrawer()"
                            class="flex-1 rounded-xl border border-white/[0.08] px-4 py-3
                                   text-sm font-medium text-slate-300
                                   transition hover:bg-white/[0.04] hover:text-white"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="flex-1 rounded-xl bg-cyan-400 px-4 py-3
                                   text-sm font-semibold text-slate-950
                                   transition hover:bg-cyan-300"
                        >
                            <span x-text="submitLabel"></span>
                        </button>

                    </div>

                </form>

            </div>

        </aside>

    </div>


    {{-- =========================================================
        DELETE CONFIRMATION
    ========================================================== --}}
    <div
        x-show="deleteOpen"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
    >

        <div
            class="absolute inset-0 bg-black/70"
            @click="deleteOpen = false"
        ></div>


        <div class="relative w-full max-w-md rounded-2xl border border-white/[0.08] bg-[#0b111c] p-6 shadow-2xl">

            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-400/[0.08] text-rose-400">
                !
            </div>

            <h3 class="mt-4 text-lg font-semibold text-white">
                Delete Machine?
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Machine
                <span
                    class="font-medium text-slate-300"
                    x-text="deleteMachineCode"
                ></span>
                akan dihapus.
            </p>

            <form
                method="POST"
                :action="deleteAction"
                class="mt-6 flex gap-3"
            >

                @csrf
                @method('DELETE')

                <button
                    type="button"
                    @click="deleteOpen = false"
                    class="flex-1 rounded-xl border border-white/[0.08] px-4 py-3
                           text-sm font-medium text-slate-300
                           hover:bg-white/[0.04]"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="flex-1 rounded-xl bg-rose-500 px-4 py-3
                           text-sm font-semibold text-white hover:bg-rose-400"
                >
                    Delete
                </button>

            </form>

        </div>

    </div>

</div>


<script>
document.addEventListener('alpine:init', () => {

    Alpine.data('machineManager', () => ({

        drawerOpen: false,
        deleteOpen: false,

        drawerTitle: 'Add Machine',
        submitLabel: 'Save Machine',

        formAction: '{{ route('machines.store') }}',
        formMethod: 'POST',

        deleteAction: '',
        deleteMachineCode: '',

        form: {
            machine_code: '',
            name: '',
            location: '',
            status: 'OFFLINE',
            temperature_threshold: '',
        },

        openCreate() {

            this.drawerTitle = 'Add Machine';
            this.submitLabel = 'Save Machine';

            this.formAction = '{{ route('machines.store') }}';
            this.formMethod = 'POST';

            this.form = {
                machine_code: '',
                name: '',
                location: '',
                status: 'OFFLINE',
                temperature_threshold: '',
            };

            this.drawerOpen = true;
        },

        openEdit(machine) {

            this.drawerTitle = 'Edit Machine';
            this.submitLabel = 'Update Machine';

            this.formAction = `/machines/${machine.id}`;
            this.formMethod = 'PUT';

            this.form = {
                machine_code: machine.machine_code ?? '',
                name: machine.name ?? '',
                location: machine.location ?? '',
                status: machine.status ?? 'OFFLINE',
                temperature_threshold: machine.temperature_threshold ?? '',
            };

            this.drawerOpen = true;
        },

        openDelete(machine) {

            this.deleteMachineCode = machine.machine_code;
            this.deleteAction = `/machines/${machine.id}`;

            this.deleteOpen = true;
        },

        closeDrawer() {

            this.drawerOpen = false;

        },

    }));

});
</script>

@endsection