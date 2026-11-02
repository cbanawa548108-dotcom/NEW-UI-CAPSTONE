<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            // Check if request expects JSON (AJAX call)
            if ($request->expectsJson() || $request->ajax()) {
                $query = InventoryItem::query();

                // Apply filters
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where('name', 'like', "%{$search}%");
                }

                if ($request->filled('category') && $request->category !== 'All Categories') {
                    $query->where('category', $request->category);
                }

                if ($request->filled('status')) {
                    switch ($request->status) {
                        case 'Available':
                            $query->where('status', 'active')
                                  ->whereRaw('stock_quantity > reorder_level');
                            break;
                        case 'Low Stock':
                            $query->where('status', 'active')
                                  ->whereRaw('stock_quantity <= reorder_level')
                                  ->where('stock_quantity', '>', 0);
                            break;
                        case 'Out of Stock':
                            $query->where('stock_quantity', '<=', 0);
                            break;
                    }
                }

                $products = $query->orderBy('name', 'asc')->get();

                // Calculate summary statistics
                $summary = [
                    'total_products' => InventoryItem::count(),
                    'active_products' => InventoryItem::active()->count(),
                    'low_stock' => InventoryItem::lowStock()->where('stock_quantity', '>', 0)->count(),
                    'out_of_stock' => InventoryItem::outOfStock()->count(),
                ];

                return response()->json([
                    'success' => true,
                    'products' => $products,
                    'summary' => $summary,
                ]);
            }

            // Return view for regular page load (not used yet, but ready for future)
            $products = InventoryItem::orderBy('name', 'asc')->get();
            return view('pages.inventory', compact('products'));

        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error fetching products',
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Error fetching products']);
        }
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'unit' => 'nullable|string|max:255',
                'price_per_unit' => 'required|numeric|min:0',
                'reorder_level' => 'nullable|numeric|min:0',
                'storage_notes' => 'nullable|string',
            ]);

            // Set defaults
            $validated['unit'] = $validated['unit'] ?? 'kg';
            $validated['stock_quantity'] = 0; // New products start with 0 stock
            $validated['reorder_level'] = $validated['reorder_level'] ?? 10;
            $validated['freshness_score'] = 100;
            $validated['spoilage_risk'] = 'Low';
            $validated['status'] = 'active';

            $product = InventoryItem::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = InventoryItem::with(['batches' => function($query) {
                $query->orderBy('expiry_date', 'asc');
            }])->findOrFail($id);

            // Calculate additional details
            $productDetails = [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'unit' => $product->unit,
                'price_per_unit' => $product->price_per_unit,
                'stock_quantity' => $product->stock_quantity,
                'reorder_level' => $product->reorder_level,
                'freshness_score' => $product->freshness_score,
                'spoilage_risk' => $product->spoilage_risk,
                'status' => $product->status,
                'storage_notes' => $product->storage_notes,
                'remaining_shelf_life' => $product->remaining_shelf_life,
                'stock_status' => $product->stock_status,
                'stock_status_badge' => $product->stock_status_badge,
                'batches' => $product->batches,
                'total_batches' => $product->batches->count(),
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];

            return response()->json([
                'success' => true,
                'product' => $productDetails,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $product = InventoryItem::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'category' => 'sometimes|required|string|max:255',
                'unit' => 'sometimes|string|max:255',
                'price_per_unit' => 'sometimes|required|numeric|min:0',
                'reorder_level' => 'sometimes|numeric|min:0',
                'storage_notes' => 'nullable|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            $product->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product->fresh(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $product = InventoryItem::findOrFail($id);

            // Check if product has batches
            if ($product->batches()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with existing batches. Please remove all batches first.'
                ], 400);
            }

            // Check if product has sales history
            if ($product->salesItems()->count() > 0) {
                // Instead of deleting, mark as inactive
                $product->update(['status' => 'inactive']);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Product has sales history and has been marked as inactive instead of deleted',
                    'product' => $product,
                ]);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        try {
            $categories = InventoryItem::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->orderBy('category')
                ->pluck('category');

            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
