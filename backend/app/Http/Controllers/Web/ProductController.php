<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('category')
            ->latest()
            ->get();

        $categories = ProductCategory::query()
            ->orderBy('name')
            ->get();

        return view('products.index', compact(
            'products',
            'categories'
        ));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');

        Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product berhasil ditambahkan.');
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): RedirectResponse {
        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active');

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $usedInSlots = DB::table('machine_slots')
            ->where('product_id', $product->id)
            ->exists();

        $usedInOrders = DB::table('order_items')
            ->where('product_id', $product->id)
            ->exists();

        if ($usedInSlots || $usedInOrders) {
            return redirect()
                ->route('products.index')
                ->with(
                    'error',
                    'Product tidak dapat dihapus karena sudah digunakan dalam slot mesin atau transaksi.'
                );
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product berhasil dihapus.');
    }
}
