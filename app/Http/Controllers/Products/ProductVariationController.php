<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\product\Product;
use App\Models\product\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductVariationController extends Controller
{
    /**
     * Display a listing of the product variations.
     *
     * @param  \App\Models\product\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function index(Product $product)
    {
        try {
            $variations = $product->variations;
            return response()->json([
                'message' => 'Product variations retrieved successfully.',
                'data' => $variations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve product variations.',
            ], 500);
        }
    }

    /**
     * Store a newly created product variation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\product\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Product $product)
    {
        try {
            $validatedData = $request->validate([
                'size' => 'nullable|string',
                'color' => 'nullable|string',
                'price' => 'required|numeric',
                'quantity' => 'required|integer',
            ]);

            $variation = $product->variations()->create($validatedData);

            return response()->json([
                'message' => 'Product variation created successfully.',
                'data' => $variation
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create product variation.',
            ], 500);
        }
    }

    /**
     * Display the specified product variation.
     *
     * @param  \App\Models\product\Product  $product
     * @param  \App\Models\product\ProductVariation  $variation
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product, ProductVariation $variation)
    {
        try {
            return response()->json([
                'message' => 'Product variation retrieved successfully.',
                'data' => $variation
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product variation not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve product variation.',
            ], 500);
        }
    }

    /**
     * Update the specified product variation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\product\Product  $product
     * @param  \App\Models\product\ProductVariation  $variation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product, ProductVariation $variation)
    {
        try {
            $validatedData = $request->validate([
                'size' => 'nullable|string',
                'color' => 'nullable|string',
                'price' => 'required|numeric',
                'quantity' => 'required|integer',
            ]);

            $variation->update($validatedData);

            return response()->json([
                'message' => 'Product variation updated successfully.',
                'data' => $variation
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product variation not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update product variation.',
            ], 500);
        }
    }

    /**
     * Remove the specified product variation.
     *
     * @param  \App\Models\product\Product  $product
     * @param  \App\Models\product\ProductVariation  $variation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product, ProductVariation $variation)
    {
        try {
            $variation->delete();

            return response()->json([
                'message' => 'Product variation deleted successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product variation not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete product variation.',
            ], 500);
        }
    }
}
