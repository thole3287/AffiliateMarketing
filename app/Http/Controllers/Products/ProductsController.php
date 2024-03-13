<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\product\Product;
use App\Models\product\ProductImagesModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['brand', 'category'])->get();
        return response()->json(['data' => $products], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:products',
            'product_price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'product_thumbbail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation for product thumbnail
            // 'product_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'url'], // Allow either file upload or URL
            'product_tags' => 'nullable',
            'product_slug' => 'required',
            'product_colors' => 'nullable',
            'product_quantity' => 'required|numeric',
            'product_short_description' => 'nullable',
            'product_long_description' => 'nullable',

        ]);

        $data = $request->all();
        // $data = $request->except('product_images');


        // Upload and save product thumbnail
        if ($request->hasFile('product_thumbbail')) {
            $image = $request->file('product_thumbbail');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('storage/product_thumbnails'), $imageName); // Di chuyển ảnh vào thư mục public
            $imageUrl = asset('storage/product_thumbnails/' . $imageName);
            $data['product_thumbbail'] = $imageUrl;
        } elseif ($request->filled('product_thumbbail_url')) {
            // Handle case when thumbnail is provided as URL
            $data['product_thumbbail'] = $request->input('product_thumbbail_url');
        }

        // Create the product with images
        $product = Product::create($data);

        // if ($request->hasFile('product_images')) {
        //     foreach ($request->file('product_images') as $image) {
        //         $imagePath = $image->store('product_images', 'public');
        //         dd($imagePath);
        //         ProductImagesModel::create([
        //             'product_image' => $imagePath,
        //             'image_prodict_id' => $product->id,
        //         ]);
        //     }
        // }

        return response()->json(['message' => 'New product added successfully.', 'data' => $product], Response::HTTP_CREATED);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['brand', 'category'])->find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $product], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:products,product_code,' . $product->id,
            'product_price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'product_thumbbail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation for product thumbnail
            // 'product_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'url'], // Allow either file upload or URL
            'product_tags' => 'nullable',
            'product_slug' => 'required',
            'product_colors' => 'nullable',
            'product_quantity' => 'required|numeric',
            'product_short_description' => 'nullable',
            'product_long_description' => 'nullable',
        ]);

        $data = $request->all();

        // Upload and save product thumbnail
        if ($request->hasFile('product_thumbbail')) {
            $image = $request->file('product_thumbbail');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('storage/product_thumbnails'), $imageName); // Di chuyển ảnh vào thư mục public
            $imageUrl = asset('storage/product_thumbnails/' . $imageName);
            $data['product_thumbbail'] = $imageUrl;
        } elseif ($request->filled('product_thumbbail_url')) {
            // Handle case when thumbnail is provided as URL
            $data['product_thumbbail'] = $request->input('product_thumbbail_url');
        }

        // Update product details
        $product->update($data);

        // if ($request->hasFile('product_images')) {
        //     foreach ($request->file('product_images') as $image) {
        //         $imagePath = $image->store('product_images', 'public');
        //         dd($imagePath);
        //         ProductImagesModel::create([
        //             'product_image' => $imagePath,
        //             'image_prodict_id' => $product->id,
        //         ]);
        //     }
        // }

        return response()->json(['message' => 'Product update successfully','data' => $product], Response::HTTP_OK);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
