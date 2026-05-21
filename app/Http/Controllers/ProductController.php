<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\Product\StoreProductRequest;
use App\Models\Product;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = Product::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'owner_id' => auth('api')->id(), // mengestrak id jwt untuk owner_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'produk berhasil dibuat',
            'data'    => [
                'id'         => $product->id,
                'name'       => $product->name,
                'price'      => (float) $product->price,
                'owner_id'   => $product->owner_id,
                'created_at' => $product->created_at->toIso8601String(),
            ]
        ], 201);
    }
}
