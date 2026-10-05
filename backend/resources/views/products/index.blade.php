@extends('layouts.dashboard')

@section('title', 'Products')

@section('content')

    <div x-data="productManager()" x-cloak class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <div class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
                    Product Management
                </div>

                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                    Products
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola produk yang tersedia pada vending machine.
                </p>
            </div>

            <button type="button" @click="openCreate()" class="inline-flex items-center justify-center gap-2 rounded-xl
                               bg-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950
                               transition hover:bg-cyan-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    class="h-4 w-4" stroke-width="2">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                </svg>

                Add Product
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


        {{-- Product Table --}}
        <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-[#0b111c]">

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="border-b border-white/[0.06] bg-white/[0.02]">

                        <tr class="text-left text-xs uppercase tracking-[0.12em] text-slate-500">

                            <th class="px-6 py-4">
                                Product
                            </th>

                            <th class="px-6 py-4">
                                Category
                            </th>

                            <th class="px-6 py-4">
                                Price
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-white/[0.05]">

                        @forelse ($products as $product)

                            <tr class="transition hover:bg-white/[0.02]">

                                {{-- Product --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="h-11 w-11 shrink-0 overflow-hidden rounded-xl border border-white/[0.06] bg-white/[0.03]">

                                            @if ($product->image)

                                                <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                                    class="h-full w-full object-cover">

                                            @else

                                                <div class="flex h-full w-full items-center justify-center text-cyan-300">

                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" class="h-5 w-5" stroke-width="1.6">
                                                        <rect x="4" y="4" width="16" height="16" rx="2" />
                                                        <path stroke-linecap="round" d="M8 15l2.5-3 2 2 2-2.5L17 15" />
                                                    </svg>

                                                </div>

                                            @endif

                                        </div>


                                        <div class="min-w-0">

                                            <div class="truncate font-medium text-white">
                                                {{ $product->name }}
                                            </div>

                                            <div class="mt-1 max-w-xs truncate text-xs text-slate-600">
                                                {{ $product->description ?: 'Tidak ada deskripsi' }}
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- Category --}}
                                <td class="px-6 py-4">

                                    <span class="rounded-lg bg-white/[0.04] px-2.5 py-1 text-xs text-slate-300">
                                        {{ $product->category?->name ?? '—' }}
                                    </span>

                                </td>


                                {{-- Price --}}
                                <td class="px-6 py-4">

                                    <div class="font-medium text-white">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </div>

                                </td>


                                {{-- Status --}}
                                <td class="px-6 py-4">

                                    @if ($product->is_active)

                                        <span class="inline-flex items-center gap-1.5 rounded-full
                                                                                     bg-emerald-400/10 px-2.5 py-1 text-[11px]
                                                                                     font-semibold uppercase tracking-[0.08em]
                                                                                     text-emerald-300 ring-1 ring-emerald-400/15">

                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>

                                            Active

                                        </span>

                                    @else

                                        <span class="inline-flex items-center gap-1.5 rounded-full
                                                                                     bg-slate-400/10 px-2.5 py-1 text-[11px]
                                                                                     font-semibold uppercase tracking-[0.08em]
                                                                                     text-slate-400 ring-1 ring-slate-400/15">

                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>

                                            Inactive

                                        </span>

                                    @endif

                                </td>


                                {{-- Actions --}}
                                <td class="px-6 py-4">

                                    <div class="flex justify-end gap-2">

                                        <button type="button" @click='openEdit(@json($product))' class="rounded-lg px-3 py-2 text-xs font-medium text-slate-300
                                                                       transition hover:bg-white/[0.05] hover:text-white">
                                            Edit
                                        </button>

                                        <button type="button" @click='openDelete(@json($product))' class="rounded-lg px-3 py-2 text-xs font-medium text-rose-400
                                                                       transition hover:bg-rose-400/[0.08]">
                                            Delete
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="px-6 py-16 text-center">

                                    <div class="text-sm font-medium text-slate-400">
                                        Belum ada product.
                                    </div>

                                    <div class="mt-1 text-xs text-slate-600">
                                        Tambahkan product pertama untuk mulai mengelola produk.
                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- =========================================================
        PRODUCT SLIDE-OVER
        ========================================================== --}}
        <div x-show="drawerOpen" class="fixed inset-0 z-[70]" @keydown.escape.window="closeDrawer()">

            {{-- Overlay --}}
            <div class="absolute inset-0 bg-black/60" @click="closeDrawer()"></div>


            {{-- Drawer --}}
            <aside x-show="drawerOpen" x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full" class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col
                               border-l border-white/[0.06] bg-[#080d17] shadow-2xl">

                {{-- Header --}}
                <div class="flex h-[73px] shrink-0 items-center justify-between border-b border-white/[0.06] px-6">

                    <div>

                        <div class="text-sm font-semibold text-white" x-text="drawerTitle"></div>

                        <div class="mt-1 text-xs text-slate-600">
                            Kelola data product
                        </div>

                    </div>

                    <button type="button" @click="closeDrawer()" class="flex h-9 w-9 items-center justify-center rounded-lg
                                       text-slate-500 transition hover:bg-white/[0.05] hover:text-white">
                        ✕
                    </button>

                </div>


                {{-- Body --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">

                    <form method="POST" :action="formAction" class="space-y-5">

                        @csrf

                        <input type="hidden" name="_method" :value="formMethod">


                        {{-- Product Name --}}
                        <div>

                            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                                Product Name
                            </label>

                            <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                               px-4 py-3 text-sm text-white outline-none
                                               placeholder:text-slate-700
                                               focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                                placeholder="Contoh: Nasi Ayam">

                        </div>


                        {{-- Category --}}
                        <div>

                            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                                Category
                            </label>

                            <select name="category_id" x-model="form.category_id" required class="w-full rounded-xl border border-white/[0.08] bg-[#0e1520]
                                               px-4 py-3 text-sm text-white outline-none
                                               focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10">

                                <option value="">
                                    Select category
                                </option>

                                @foreach ($categories as $category)

                                    <option value="{{ $category->id }}">
                                        {{ $category->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Price --}}
                        <div>

                            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                                Price
                            </label>

                            <div class="relative">

                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-600">
                                    Rp
                                </span>

                                <input type="number" name="price" x-model="form.price" required min="0" step="0.01" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                                   py-3 pl-11 pr-4 text-sm text-white outline-none
                                                   focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                                    placeholder="15000">

                            </div>

                        </div>


                        {{-- Description --}}
                        <div>

                            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                                Description
                            </label>

                            <textarea name="description" x-model="form.description" rows="4" class="w-full resize-none rounded-xl border border-white/[0.08] bg-white/[0.03]
                                               px-4 py-3 text-sm text-white outline-none
                                               placeholder:text-slate-700
                                               focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                                placeholder="Deskripsi product..."></textarea>

                        </div>


                        {{-- Image URL --}}
                        <div>

                            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                                Image URL
                            </label>

                            <input type="url" name="image" x-model="form.image" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                               px-4 py-3 text-sm text-white outline-none
                                               placeholder:text-slate-700
                                               focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                                placeholder="https://...">

                            <p class="mt-1.5 text-[11px] text-slate-600">
                                Untuk sementara gunakan URL gambar.
                            </p>

                        </div>


                        {{-- Active --}}
                        <div>

                            <label
                                class="flex items-center gap-3 rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-3">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                    class="h-4 w-4 rounded border-white/20 bg-transparent text-cyan-400 focus:ring-cyan-400">

                                <div>

                                    <div class="text-sm font-medium text-white">
                                        Product active
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-600">
                                        Product dapat ditampilkan dan digunakan.
                                    </div>

                                </div>

                            </label>

                        </div>


                        {{-- Validation errors --}}
                        @if ($errors->any())

                            <div class="rounded-xl border border-rose-400/15 bg-rose-400/[0.06] p-4">

                                <div class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-300">
                                    Please check the form
                                </div>

                                <div class="mt-2 space-y-1 text-xs text-rose-300/80">

                                    @foreach ($errors->all() as $error)

                                        <div>
                                            {{ $error }}
                                        </div>

                                    @endforeach

                                </div>

                            </div>

                        @endif


                        {{-- Actions --}}
                        <div class="flex gap-3 border-t border-white/[0.06] pt-6">

                            <button type="button" @click="closeDrawer()" class="flex-1 rounded-xl border border-white/[0.08] px-4 py-3
                                               text-sm font-medium text-slate-300
                                               transition hover:bg-white/[0.04] hover:text-white">
                                Cancel
                            </button>

                            <button type="submit" class="flex-1 rounded-xl bg-cyan-400 px-4 py-3
                                               text-sm font-semibold text-slate-950
                                               transition hover:bg-cyan-300">
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
        <div x-show="deleteOpen" class="fixed inset-0 z-[80] flex items-center justify-center p-4">

            <div class="absolute inset-0 bg-black/70" @click="deleteOpen = false"></div>


            <div class="relative w-full max-w-md rounded-2xl border border-white/[0.08] bg-[#0b111c] p-6 shadow-2xl">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-400/[0.08] text-rose-400">
                    !
                </div>

                <h3 class="mt-4 text-lg font-semibold text-white">
                    Delete Product?
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Product
                    <span class="font-medium text-slate-300" x-text="deleteProductName"></span>
                    akan dihapus.
                </p>

                <form method="POST" :action="deleteAction" class="mt-6 flex gap-3">

                    @csrf
                    @method('DELETE')

                    <button type="button" @click="deleteOpen = false" class="flex-1 rounded-xl border border-white/[0.08] px-4 py-3
                                       text-sm font-medium text-slate-300
                                       hover:bg-white/[0.04]">
                        Cancel
                    </button>

                    <button type="submit" class="flex-1 rounded-xl bg-rose-500 px-4 py-3
                                       text-sm font-semibold text-white hover:bg-rose-400">
                        Delete
                    </button>

                </form>

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.data('productManager', () => ({

                drawerOpen: false,
                deleteOpen: false,

                drawerTitle: 'Add Product',
                submitLabel: 'Save Product',

                formAction: '{{ route('products.store') }}',
                formMethod: 'POST',

                deleteAction: '',
                deleteProductName: '',

                form: {
                    name: '',
                    category_id: '',
                    description: '',
                    price: '',
                    image: '',
                    is_active: true,
                },

                openCreate() {

                    this.drawerTitle = 'Add Product';
                    this.submitLabel = 'Save Product';

                    this.formAction = '{{ route('products.store') }}';
                    this.formMethod = 'POST';

                    this.form = {
                        name: '',
                        category_id: '',
                        description: '',
                        price: '',
                        image: '',
                        is_active: true,
                    };

                    this.drawerOpen = true;
                },

                openEdit(product) {
                    this.drawerTitle = 'Edit Product';
                    this.submitLabel = 'Update Product';

                    this.formAction = `/products/${product.id}`;
                    this.formMethod = 'PUT';

                    this.form = {
                        name: product.name ?? '',
                        category_id: product.category_id ?? '',
                        description: product.description ?? '',
                        price: product.price ?? '',
                        image: product.image ?? '',
                        is_active:
                            product.is_active === true ||
                            product.is_active === 1 ||
                            product.is_active === '1',
                    };

                    this.drawerOpen = true;
                },

                openDelete(product) {

                    this.deleteProductName = product.name;
                    this.deleteAction = `/products/${product.id}`;

                    this.deleteOpen = true;
                },

                closeDrawer() {

                    this.drawerOpen = false;

                },

            }));

        });
    </script>

@endsection