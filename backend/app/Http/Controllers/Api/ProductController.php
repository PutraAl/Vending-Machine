<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max((int) $request->integer('per_page', 10), 1),
            100
        );

        $products = Product::query()
            ->with('category')
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $query->where(
                        'name',
                        'ilike',
                        '%' . $request->string('search') . '%'
                    );
                }
            )
            ->when(
                $request->filled('category_id'),
                function ($query) use ($request) {
                    $query->where(
                        'category_id',
                        $request->integer('category_id')
                    );
                }
            )
            ->when(
                $request->user() === null,
                function ($query) {
                    // Kiosk/public hanya boleh melihat produk aktif.
                    $query->where('is_active', true);
                }
            )
            ->when(
                $request->user() !== null && $request->has('is_active'),
                function ($query) use ($request) {
                    // User yang sudah login tetap bisa memakai filter is_active.
                    $query->where(
                        'is_active',
                        $request->boolean('is_active')
                    );
                }
            )
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products,
        ]);
    }

    public function store(
        StoreProductRequest $request
    ): JsonResponse {
        $product = Product::create(
            $request->validated()
        );

        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => $product,
        ]);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): JsonResponse {
        $product->update(
            $request->validated()
        );

        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->fresh('category'),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if (
            $product->machineSlots()->exists()
            || $product->orderItems()->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Product cannot be deleted because it is already used by machine slots or orders.',
            ], 409);
        }

        try {
            $product->delete();
        } catch (QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Product cannot be deleted.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
            'data' => null,
        ]);
    }
}