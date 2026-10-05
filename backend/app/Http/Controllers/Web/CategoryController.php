<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::query()
            ->withCount('products')
            ->latest()
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        ProductCategory::create($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category berhasil ditambahkan.');
    }

    public function update(
        UpdateProductCategoryRequest $request,
        ProductCategory $category
    ): RedirectResponse {
        $category->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category berhasil diperbarui.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'Category tidak dapat dihapus karena masih digunakan oleh product.');
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category berhasil dihapus.');
    }
}