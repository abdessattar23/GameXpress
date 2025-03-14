<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // Define the stock threshold
    const LOW_STOCK_THRESHOLD = 5;

    /**
     * List all products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $products = Product::with(['category', 'images'])->latest()->get();

            return response()->json([
                'status' => 'success',
                'data' => $products
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Create a new product
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'status' => 'required|string|in:available,unavailable',
                'category_id' => 'required|exists:categories,id',
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'primary_image' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate slug from name
            $slug = Str::slug($request->name);

            // Check if slug exists, if yes, append a unique identifier
            $slugCount = Product::where('slug', 'LIKE', $slug . '%')->count();
            if ($slugCount > 0) {
                $slug = $slug . '-' . ($slugCount + 1);
            }

            // Create the product
            $product = Product::create([
                'name' => $request->name,
                'slug' => $slug,
                'price' => $request->price,
                'stock' => $request->stock,
                'status' => $request->status,
                'category_id' => $request->category_id
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'image_url' => $path,
                        'is_primary' => $index === $request->primary_image
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product->fresh(['category', 'images'])
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific product
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $product = Product::with(['category', 'images'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $product
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an existing product
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Find the product
            $product = Product::findOrFail($id);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'status' => 'sometimes|required|string|in:available,unavailable',
                'category_id' => 'sometimes|required|exists:categories,id',
                'images' => 'sometimes|array',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'primary_image' => 'sometimes|required|integer|min:0',
                'delete_images' => 'sometimes|array',
                'delete_images.*' => 'required|exists:product_images,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update slug if name changes
            if ($request->has('name') && $request->name !== $product->name) {
                $slug = Str::slug($request->name);

                // Check if slug exists, if yes, append a unique identifier
                $slugCount = Product::where('slug', 'LIKE', $slug . '%')
                    ->where('id', '!=', $id)
                    ->count();
                if ($slugCount > 0) {
                    $slug = $slug . '-' . ($slugCount + 1);
                }

                $product->slug = $slug;
            }

            // Update product fields
            if ($request->has('name')) $product->name = $request->name;
            if ($request->has('price')) $product->price = $request->price;
            if ($request->has('stock')) $product->stock = $request->stock;
            if ($request->has('status')) $product->status = $request->status;
            if ($request->has('category_id')) $product->category_id = $request->category_id;

            $product->save();

            // Handle image deletions
            if ($request->has('delete_images')) {
                $product->images()->whereIn('id', $request->delete_images)->delete();
            }

            // Handle new image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'image_url' => $path,
                        'is_primary' => $request->has('primary_image') && $index === $request->primary_image
                    ]);
                }
            } else if ($request->has('primary_image')) {
                // Update primary image status if no new images but primary_image is set
                $product->images()->update(['is_primary' => false]);
                $product->images()->where('id', $request->primary_image)->update(['is_primary' => true]);
            }

            // Check if stock was updated and is below threshold
            if ($request->has('stock') && $product->stock <= self::LOW_STOCK_THRESHOLD) {
                // Notify all admin users
                $admins = User::role('admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new LowStockNotification($product, $product->stock));
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product->fresh(['category', 'images'])
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
