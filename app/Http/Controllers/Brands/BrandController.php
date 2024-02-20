<?php

namespace App\Http\Controllers\Brands;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;


class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::all();
        return response()->json(['data' => $brands]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Kiểm tra loại và kích thước file ảnh
            'image_url' => 'nullable|url', // Kiểm tra định dạng URL
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('storage/images/brands'), $imageName); // Di chuyển ảnh vào thư mục public
            $imageUrl = asset('storage/images/brands/' . $imageName);
        } elseif ($request->filled('image_url')) {
            $imageUrl = $request->input('image_url');
        } else {
            $imageUrl = null;
        }


        $brand = Brand::create([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'image' => $imageUrl,
        ]);

        return response()->json(['data' => $brand], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }
        return response()->json(['data' => $brand]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Kiểm tra loại và kích thước file ảnh
            'image_url' => 'nullable|url', // Kiểm tra định dạng URL
        ]);

        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $image->getClientOriginalExtension();            $image->move(public_path('storage/images/brands'), $imageName); // Di chuyển ảnh vào thư mục public
            $imageUrl = asset('storage/images/brands/' . $imageName);
        } elseif ($request->filled('image_url')) {
            $imageUrl = $request->input('image_url');
        } else {
            $imageUrl = null;
        }

        $brand->update([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'image' => $imageUrl,
        ]);

        return response()->json(['data' => $brand]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }
        
        if ($brand->image) {
            $imagePath = public_path('storage/images/brands/') . basename($brand->image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $brand->delete();

        return response()->json(['message' => 'Brand deleted']);
    }
}
