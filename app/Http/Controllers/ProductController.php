<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\Product\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Requests\Product\UpdateProductRequest;
use Illuminate\Support\Facades\Validator;

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
            'message' => 'Product created successfully', // tesk respon yang sama di contoh case saya sesuaikan disini
            'data'    => new ProductResource($product)
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page'       => 'integer|min:1',
            'limit'      => 'integer|min:1|max:100',
            'search'     => 'nullable|string',
            'sort_by'    => 'nullable|in:name,price,created_at',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $limit     = $request->input('limit', 10);
        $search    = $request->input('search');
        $sortBy    = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', $sortBy === 'created_at' ? 'desc' : 'asc');

        $query = Product::query();

        $query->when($search, function ($q, $search) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        });

        $products = $query->orderBy($sortBy, $sortOrder)
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Product list retrieved successfully',
            'data'    => ProductResource::collection($products->items()),
            'pagination' => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'total_pages'  => $products->lastPage(),
            ]
        ], 200);
    }

    public function show($id): JsonResponse
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The id must be a valid UUID',
                'errors'  => $validator->errors()
            ], 422);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new ProductResource($product)
        ], 200);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $validator = Validator::make(
            ['id' => $id],
            ['id' => ['required', 'uuid']],
            ['id.uuid' => 'The id must be a valid UUID.']
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('id'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $product = Product::query()->where('id', $id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        if ((string) $product->owner_id !== (string) auth('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You can only update your own products',
            ], 403);
        }

        $product->fill($request->validated());
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data'    => new ProductResource($product)
        ], 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $validator = Validator::make(
            ['id' => $id],
            ['id' => ['required', 'uuid']],
            ['id.uuid' => 'The id must be a valid UUID.']
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('id'),
                'errors'  => $validator->errors()
            ], 422);
        }

        $product = Product::query()->where('id', $id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        if ((string) $product->owner_id !== (string) auth('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You can only delete your own products',
            ], 403);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ], 200);
    }
}
