<?php

namespace App\Http\Controllers\Brands;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Http\Services\UploadService;



class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $uploadService;
    public function __construct(UploadService $uploadService){
        $this->uploadService = $uploadService;
    }
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

        $imageUrl = $this->uploadService->updateSingleImage($request, 'image', 'image_url', 'brands', false);
        if(is_string($imageUrl) || is_null($imageUrl))
        {
            $brand = Brand::create([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'image' => $imageUrl,
            ]);
            return response()->json(['data' => $brand], 201);

        }else{
            if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
                return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
            }
        }

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

        $imageUrl = $this->uploadService->updateSingleImage($request, 'image', 'image_url', 'brands', false);
        if(is_string($imageUrl) || is_null($imageUrl))
        {
            $brand->update([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'image' => $imageUrl,
            ]);

            return response()->json(['data' => $brand]);

        }else
        {
            if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
                return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
            }
        }


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
            $imagePath = public_path('public/brands/') . basename($brand->image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $brand->delete();

        return response()->json(['message' => 'Brand deleted']);
    }
}
