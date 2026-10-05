<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::query()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    public function store(
        StoreProductCategoryRequest $request
    ): JsonResponse {
        $category = ProductCategory::create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function show(
        ProductCategory $category
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data' => $category,
        ]);
    }

    public function update(
        UpdateProductCategoryRequest $request,
        ProductCategory $category
    ): JsonResponse {
        $category->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(
        ProductCategory $category
    ): JsonResponse {
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Category cannot be deleted because it is still used by products.',
            ], 409);
        }

        try {
            $category->delete();
        } catch (QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Category cannot be deleted.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
            'data' => null,
        ]);
    }
}