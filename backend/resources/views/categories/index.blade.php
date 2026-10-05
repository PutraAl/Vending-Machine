@extends('layouts.dashboard')

@section('title', 'Categories')

@section('content')
    <div x-data="categoryManager()" x-cloak class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="text-xs font-medium uppercase tracking-[0.18em] text-cyan-400">
                    Product Management
                </div>

                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                    Categories
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola kategori produk vending machine.
                </p>
            </div>

            <button type="button" @click="openCreate()" class="inline-flex items-center justify-center gap-2 rounded-xl
                       bg-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950
                       transition hover:bg-cyan-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    class="h-4 w-4" stroke-width="2">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                </svg>

                Add Category
            </button>
        </div>

        {{-- Flash message --}}
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
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Products</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-white/[0.05]">
                        @forelse ($categories as $category)
                            <tr class="transition hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white">
                                        {{ $category->name }}
                                    </div>

                                    @if ($category->is_active ?? true)
                                        <div class="mt-1 text-[11px] text-emerald-400">
                                            Active
                                        </div>
                                    @else
                                        <div class="mt-1 text-[11px] text-slate-500">
                                            Inactive
                                        </div>
                                    @endif
                                </td>

                                <td class="max-w-md px-6 py-4 text-slate-400">
                                    {{ $category->description ?: '—' }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-lg bg-white/[0.04] px-2.5 py-1 text-xs text-slate-300">
                                        {{ $category->products_count }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click='openEdit(@json($category))' class="rounded-lg px-3 py-2 text-xs font-medium text-slate-300
                                                       transition hover:bg-white/[0.05] hover:text-white">
                                            Edit
                                        </button>

                                        <button type="button" @click='openDelete(@json($category))' class="rounded-lg px-3 py-2 text-xs font-medium text-rose-400
                                                       transition hover:bg-rose-400/[0.08]">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <div class="text-sm font-medium text-slate-400">
                                        Belum ada category.
                                    </div>

                                    <div class="mt-1 text-xs text-slate-600">
                                        Tambahkan category pertama untuk mulai mengelola product.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Slide-over --}}
        <div x-show="drawerOpen" class="fixed inset-0 z-[70]" @keydown.escape.window="closeDrawer()">
            {{-- Overlay --}}
            <div class="absolute inset-0 bg-black/60" @click="closeDrawer()"></div>

            {{-- Drawer --}}
            <aside x-show="drawerOpen" x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full" class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col
                       border-l border-white/[0.06] bg-[#080d17] shadow-2xl">
                {{-- Drawer header --}}
                <div class="flex h-[73px] shrink-0 items-center justify-between border-b border-white/[0.06] px-6">
                    <div>
                        <div class="text-sm font-semibold text-white" x-text="drawerTitle"></div>
                        <div class="mt-1 text-xs text-slate-600">
                            Kelola data category
                        </div>
                    </div>

                    <button type="button" @click="closeDrawer()" class="flex h-9 w-9 items-center justify-center rounded-lg
                               text-slate-500 transition hover:bg-white/[0.05] hover:text-white">
                        ✕
                    </button>
                </div>

                {{-- Drawer body --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                    <form method="POST" :action="formAction" class="space-y-5">
                        @csrf
                        <input type="hidden" name="_method" :value="formMethod">

                        {{-- Name --}}
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                                Category Name
                            </label>

                            <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03]
                                       px-4 py-3 text-sm text-white outline-none
                                       placeholder:text-slate-700
                                       focus:border-cyan-400/40 focus:ring-2 focus:ring-cyan-400/10"
                                placeholder="Contoh: Makanan">
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
                                placeholder="Deskripsi category..."></textarea>
                        </div>

                  

                        {{-- Validation --}}
                        @if ($errors->any())
                            <div class="rounded-xl border border-rose-400/15 bg-rose-400/[0.06] p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-300">
                                    Please check the form
                                </div>

                                <div class="mt-2 space-y-1 text-xs text-rose-300/80">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex gap-3 border-t border-white/[0.06] pt-6">
                            <button type="button" @click="closeDrawer()" class="flex-1 rounded-xl border border-white/[0.08] px-4 py-3 text-sm font-medium text-slate-300
                                       transition hover:bg-white/[0.04] hover:text-white">
                                Cancel
                            </button>

                            <button type="submit" class="flex-1 rounded-xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950
                                       transition hover:bg-cyan-300">
                                <span x-text="submitLabel"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </aside>
        </div>

        {{-- Delete confirmation --}}
        <div x-show="deleteOpen" class="fixed inset-0 z-[80] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" @click="deleteOpen = false"></div>

            <div class="relative w-full max-w-md rounded-2xl border border-white/[0.08] bg-[#0b111c] p-6 shadow-2xl">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-400/[0.08] text-rose-400">
                    !
                </div>

                <h3 class="mt-4 text-lg font-semibold text-white">
                    Delete Category?
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Category
                    <span class="font-medium text-slate-300" x-text="deleteCategoryName"></span>
                    akan dihapus.
                </p>

                <form method="POST" :action="deleteAction" class="mt-6 flex gap-3">
                    @csrf
                    @method('DELETE')

                    <button type="button" @click="deleteOpen = false"
                        class="flex-1 rounded-xl border border-white/[0.08] px-4 py-3 text-sm font-medium text-slate-300 hover:bg-white/[0.04]">
                        Cancel
                    </button>

                    <button type="submit"
                        class="flex-1 rounded-xl bg-rose-500 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-400">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('categoryManager', () => ({
                drawerOpen: false,
                deleteOpen: false,

                drawerTitle: 'Add Category',
                submitLabel: 'Save Category',

                formAction: '{{ route('categories.store') }}',
                formMethod: 'POST',

                deleteAction: '',
                deleteCategoryName: '',

                form: {
                    name: '',
                    description: '',
                    is_active: true,
                },

                openCreate() {
                    this.drawerTitle = 'Add Category';
                    this.submitLabel = 'Save Category';
                    this.formAction = '{{ route('categories.store') }}';
                    this.formMethod = 'POST';

                    this.form = {
                        name: '',
                        description: '',
                     
                    };

                    this.drawerOpen = true;
                },

                openEdit(category) {
                    this.drawerTitle = 'Edit Category';
                    this.submitLabel = 'Update Category';
                    this.formAction = `/categories/${category.id}`;
                    this.formMethod = 'PUT';

                    this.form = {
                        name: category.name ?? '',
                        description: category.description ?? '',
                    
                    };

                    this.drawerOpen = true;
                },

                openDelete(category) {
                    this.deleteCategoryName = category.name;
                    this.deleteAction = `/categories/${category.id}`;
                    this.deleteOpen = true;
                },

                closeDrawer() {
                    this.drawerOpen = false;
                },
            }));
        });
    </script>
@endsection