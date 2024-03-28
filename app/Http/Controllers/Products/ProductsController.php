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

    public function __construct(UploadService $uploadService){
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

        $image_detail = $this->uploadService->uploadMultipleImages($request, 'product_images');

        if($image_detail){
            $saved_images = [];
            foreach ( $image_detail as $image_path) {
                $product_image = new ProductImagesModel();
                $product_image->product_id = $product->id;
                $product_image->image_path = $image_path;
                $product_image->save();
                $saved_images[] = $product_image->image_path;
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
    // public function update(Request $request, $id)
    // {
    //     $product = Product::findOrFail($id);

    //     $request->validate([
    //         'product_name' => 'required|string',
    //         'product_code' => 'required|string|unique:products,product_code,' . $product->id,
    //         'product_price' => 'required|numeric',
    //         'category_id' => 'required|exists:categories,id',
    //         'brand_id' => 'required|exists:brands,id',
    //         'product_thumbbail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation for product thumbnail
    //         // 'product_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'url'], // Allow either file upload or URL
    //         'product_tags' => 'nullable',
    //         'product_slug' => 'required',
    //         'product_colors' => 'nullable',
    //         'product_quantity' => 'required|numeric',
    //         'product_short_description' => 'nullable',
    //         'product_long_description' => 'nullable',
    //     ]);

    //     $data = $request->all();

    //     // Upload and save product thumbnail
    //     if ($request->hasFile('product_thumbbail')) {
    //         $image = $request->file('product_thumbbail');
    //         $imageName = time() . '.' . $image->getClientOriginalExtension();
    //         $image->move(public_path('storage/product_thumbnails'), $imageName); // Di chuyển ảnh vào thư mục public
    //         $imageUrl = asset('storage/product_thumbnails/' . $imageName);
    //         $data['product_thumbbail'] = $imageUrl;
    //     } elseif ($request->filled('product_thumbbail_url')) {
    //         // Handle case when thumbnail is provided as URL
    //         $data['product_thumbbail'] = $request->input('product_thumbbail_url');
    //     }

    //     // Update product details
    //     $product->update($data);

    //     // if ($request->hasFile('product_images')) {
    //     //     foreach ($request->file('product_images') as $image) {
    //     //         $imagePath = $image->store('product_images', 'public');
    //     //         dd($imagePath);
    //     //         ProductImagesModel::create([
    //     //             'product_image' => $imagePath,
    //     //             'image_prodict_id' => $product->id,
    //     //         ]);
    //     //     }
    //     // }

    //     return response()->json(['message' => 'Product update successfully', 'data' => $product], Response::HTTP_OK);
    // }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'string|unique:products',
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
        $product->update($data);

        $image_detail = $this->uploadService->uploadMultipleImages($request, 'product_images');

        // if($image_detail){
        //     $saved_images = [];
        //     foreach ($image_detail as $image_path) {
        //         $product_image = ProductImagesModel::updateOrCreate(
        //             ['product_id' => $product->id, 'id' => $request->input('id_product_image')], // Điều kiện tìm kiếm để cập nhật hoặc tạo mới
        //             ['image_path' => $image_path['image_path']] // Dữ liệu cần cập nhật hoặc tạo mới
        //         );
        //         $saved_images[] = $product_image->image_path;
        //     }

        // }
        if ($image_detail) {
            $saved_images = [];
            foreach ($image_detail as $key => $image_path) {
                $product_image = ProductImagesModel::updateOrCreate(
                    ['product_id' => $product->id, 'id' => $request->input('id_product_image')[$key]], // Điều kiện tìm kiếm để cập nhật hoặc tạo mới
                    ['image_path' => $image_path['image_path']] // Dữ liệu cần cập nhật hoặc tạo mới
                );
                $saved_images[] = $product_image->image_path;
            }
        }



        $variationsData = []; // Mảng để lưu trữ thông tin biến thể
        if ($request->has('variations')) {
            foreach ($request->variations as $variation) {
                if (isset($variation['id'])) {
                    // Cập nhật biến thể hiện có
                    $product->variations()->where('id', $variation['id'])->update($variation);
                } else {
                    // Thêm biến thể mới
                    $product->variations()->create($variation);
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
            foreach ($product_image->image_path as $image)
            {
                if(!filter_var($image, FILTER_VALIDATE_URL))
                {
                    $imagePath = str_replace(asset(''), '', $product_image->image_path); // Lấy đường dẫn tương đối
                    Storage::delete($imagePath); // Xóa hình ảnh sử dụng facade Storage
                }
            }
        }

        return response()->json(['message' => 'Product deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
