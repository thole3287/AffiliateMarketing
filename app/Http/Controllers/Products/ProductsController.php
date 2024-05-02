<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Services\UploadService;
use App\Models\product\Product;
use App\Models\product\ProductImagesModel;
use App\Models\product\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    public function index()
    {
        $products = Product::with(['brand', 'category', 'variations'])->where('product_status', 'active')->get();
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
            'product_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'url'], // Allow either file upload or URL
            'product_tags' => 'nullable',
            'product_slug' => 'required',
            'product_colors' => 'nullable',
            'product_quantity' => 'required|numeric',
            'product_short_description' => 'nullable',
            'product_long_description' => 'nullable',
            'variations.*.size' => 'nullable|string',
            'variations.*.color' => 'nullable|string',
            'variations.*.price' => 'nullable|numeric',
            'variations.*.quantity' => 'nullable|integer',

        ]);

        $data = $request->except(['image_detail', 'variations', 'image_detail_url']);
        // Upload and save product thumbnail
        $imageUrl = $this->uploadService->updateSingleImage($request, 'product_thumbbail', 'product_thumbbail_url', 'product_thumbnails', false);
        if (is_string($imageUrl) || is_null($imageUrl)) {
            $data['product_thumbbail'] = $imageUrl;
        } else {
            if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
                return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
            }
        }


        // Create the product with images
        $product = Product::create($data);

        $image_detail = $this->uploadService->uploadMultipleImages($request, 'image_detail', 'image_detail_url', 'product_images', false);
        // dd( $image_detail);
        if ($image_detail && is_string($imageUrl) || is_null($imageUrl)) {
            $saved_images = [];
            foreach ($image_detail as $image_path) {
                $product_image = new ProductImagesModel();
                $product_image->product_id = $product->id;
                $product_image->image_path = $image_path;
                $product_image->save();
                $saved_images[] = $product_image->image_path;
            }
        } else {
            if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
                return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
            }
        }

        $variationsData = []; // Mảng để lưu trữ thông tin biến thể
        if ($request->has('variations')) {
            foreach ($request->variations as $variation) {
                // Đảm bảo dữ liệu biến thể không trống trước khi lưu
                if (!empty($variation)) {
                    // Sử dụng mô hình ProductVariation để lưu trữ thông tin vào bảng khác
                    $variationModel  = ProductVariation::create([
                        'product_id' => $product->id, // Hoặc bất kỳ khóa ngoại nào kết nối với sản phẩm
                        'size' => $variation['size'] ?? null,
                        'color' => $variation['color'] ?? null,
                        'price' => $variation['price'] ?? null,
                        'quantity' => $variation['quantity'] ?? null,
                    ]);
                    $variationsData[] = $variationModel;
                }
            }
        }
        return response()->json([
            'message' => 'New product added successfully.',
            'data' => [
                'product' => $product,
                'images' => $saved_images,
                'variations' => $variationsData,
            ]
        ], Response::HTTP_CREATED);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['brand', 'category', 'variations'])->where('product_status', 'active')->find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $product], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'string|unique:products,product_code,' . $product->id,
            'product_price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'product_thumbbail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation for product thumbnail
            'product_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'url'], // Allow either file upload or URL
            'product_tags' => 'nullable',
            'product_slug' => 'required',
            'product_colors' => 'nullable',
            'product_quantity' => 'required|numeric',
            'product_short_description' => 'nullable',
            'product_long_description' => 'nullable',
            'variations.*.size' => 'nullable|string',
            'variations.*.color' => 'nullable|string',
            'variations.*.price' => 'nullable|numeric',
            'variations.*.quantity' => 'nullable|integer',
        ]);

        $data = $request->except(['image_detail', 'variations', 'image_detail_url']);

        $imageUrl = $this->uploadService->updateSingleImage($request, 'product_thumbbail', 'product_thumbbail_url', 'product_thumbnails', false);
        if (is_string($imageUrl)) {
            $data['product_thumbbail'] = $imageUrl;
        }
        if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
            return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
        }

        // Update product data
        $product->update($data);

        // Update product images
        $saved_images = [];
        if ($request->has('product_images')) {
            $image_detail = $this->uploadService->uploadMultipleImages($request, 'image_detail', 'image_detail_url', 'product_images', false);
            if ($image_detail) {
                foreach ($image_detail as $image_path) {
                    $product_image = new ProductImagesModel();
                    $product_image->product_id = $product->id;
                    $product_image->image_path = $image_path;
                    $product_image->save();
                    $saved_images[] = $product_image->image_path;
                }
            }
        }

        $updatedImages = [];

        // Update product images
        if ($request->has('product_images')) {
            foreach ($request->product_images as $image) {
                // Đảm bảo dữ liệu hình ảnh không trống và có trường id
                if (!empty($image['id'])) {
                    $productImage = ProductImagesModel::find($image['id']);
                    if ($productImage) {
                        if (isset($image['image_path'])) {
                            $productImage->update(['image_path' => $image['image_path']]);
                        }
                        $updatedImages[] = $productImage;
                    }
                } else {
                    // Nếu không có 'id', tạo mới hình ảnh sản phẩm
                    $newProductImage = ProductImagesModel::create([
                        'product_id' => $product->id,
                        'image_path' => $image['image_path'] ?? null,
                    ]);
                    $updatedImages[] = $newProductImage;
                }
            }
        }

        // Update product variations
        // $variationsData = []; // Mảng để lưu trữ thông tin biến thể
        // if ($request->has('variations')) {
        //     $product->variations()->delete(); // Xóa các biến thể cũ trước khi thêm mới
        //     foreach ($request->variations as $variation) {
        //         // Đảm bảo dữ liệu biến thể không trống trước khi lưu
        //         if (!empty($variation)) {
        //             // Sử dụng mô hình ProductVariation để lưu trữ thông tin vào bảng khác
        //             $variationModel = ProductVariation::create([
        //                 'product_id' => $product->id, // Hoặc bất kỳ khóa ngoại nào kết nối với sản phẩm
        //                 'size' => $variation['size'] ?? null,
        //                 'color' => $variation['color'] ?? null,
        //                 'price' => $variation['price'] ?? null,
        //                 'quantity' => $variation['quantity'] ?? null,
        //             ]);
        //             $variationsData[] = $variationModel;
        //         }
        //     }
        // }
        $updatedVariations = [];

        if ($request->has('variations')) {
            foreach ($request->variations as $variation) {
                // Đảm bảo dữ liệu biến thể không trống trước khi cập nhật
                if (!empty($variation['id'])) {
                    // Tìm biến thể theo ID
                    // dd($variation['id']);
                    $productVariation = ProductVariation::find($variation['id']);
                    // dd($productVariation, $variation['size']);
                    // Cập nhật các trường dữ liệu chỉ khi chúng được gửi từ form
                    $updateData = [];
                    if (isset($variation['size'])) {
                        $updateData['size'] = $variation['size'];
                    }
                    if (isset($variation['color'])) {
                        $updateData['color'] = $variation['color'];
                    }
                    if (isset($variation['price'])) {
                        $updateData['price'] = $variation['price'];
                    }
                    if (isset($variation['quantity'])) {
                        $updateData['quantity'] = $variation['quantity'];
                    }

                    // Cập nhật biến thể
                    $productVariation->update($updateData);
                    $updatedVariations[] = $productVariation;
                }
            }
        }

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => [
                'product' => $product,
                'images' => $updatedImages,
                'variations' => $updatedVariations,
            ]
        ], Response::HTTP_OK);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);
        $product_image = ProductImagesModel::where('product_id', $id)->get();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        $product->delete();
        // Xóa hình ảnh sản phẩm
        if ($product->product_thumbbail) {
            $imagePath = str_replace(asset(''), '', $product->product_thumbbail); // Lấy đường dẫn tương đối
            Storage::delete($imagePath); // Xóa hình ảnh sử dụng facade Storage
        }
        // Xóa tất cả biến thể của sản phẩm
        $product->variations()->delete();
        $product->images()->delete();
        if ($product_image->image_path) {
            foreach ($product_image->image_path as $image) {
                if (!filter_var($image, FILTER_VALIDATE_URL)) {
                    $imagePath = str_replace(asset(''), '', $product_image->image_path); // Lấy đường dẫn tương đối
                    Storage::delete($imagePath); // Xóa hình ảnh sử dụng facade Storage
                }
            }
        }

        return response()->json(['message' => 'Product deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
