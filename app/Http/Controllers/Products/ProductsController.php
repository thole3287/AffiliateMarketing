<?php

namespace App\Http\Controllers\Products;

use App\Elasticsearch\BaseElastic;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Services\UploadService;
use App\Models\product\Product;
use App\Models\product\ProductImagesModel;
use App\Models\product\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;

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
    public function searchSQL(Request $request)
    {
        $query = DB::table('products')
            ->select('products.*')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('product_variations', 'products.id', '=', 'product_variations.product_id');

        // Thêm các điều kiện tìm kiếm nếu có
        if ($request->has('brand_name')) {
            $query->where('brands.name', $request->input('brand_name'));
        }

        if ($request->has('product_price')) {
            $price = $request->input('product_price');
            if (isset($price['min']) && isset($price['max'])) {
                $query->whereBetween('products.product_price', [$price['min'], $price['max']]);
            } else {
                // Nếu chỉ có giá tối đa được chỉ định, tìm các sản phẩm có giá chính xác bằng giá đó
                $query->where('products.product_price', $price);
            }
        }

        if ($request->has('product_name')) {
            $query->where('products.product_name', 'LIKE', '%' . $request->input('product_name') . '%');
        }

        if ($request->has('category_name')) {
            $query->where('categories.name', $request->input('category_name'));
        }

        if ($request->has('product_status')) {
            $query->where('products.product_status', $request->input('product_status'));
        }

        // Lấy tất cả các thuộc tính được gửi trong request
        $attributes = $request->except(['brand_name', 'product_price', 'product_name', 'category_name', 'product_status']);

        // Thêm điều kiện cho tất cả các thuộc tính
        foreach ($attributes as $attributeName => $attributeValue) {
            $query->where("product_variations.attributes->{$attributeName}", $attributeValue);
        }

        // Thực hiện truy vấn và lấy kết quả
        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu tương ứng.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($products);
    }

    public function search(Request $request)
    {
        $elasticModel = new BaseElastic();
        $params = [
            'index' => 'products',
            'type' => '_search',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [],
                    ],
                ],
                'size' => 10000
            ],
        ];

        // Thêm các điều kiện tìm kiếm nếu có
        if ($request->has('brand_name')) {
            // Thêm một điều kiện bool must với tất cả các điều kiện nested đã tạo
            $params['body']['query']['bool']['must'][] = [
                'nested' => [
                    'path' => 'brands',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['match' => ['brands.name' => $request->input('brand_name')]]
                            ]
                        ]
                    ]
                ]
            ];
        }

        if ($request->has('product_price')) {
            $price = $request->input('product_price');
            if (isset($price['min']) && isset($price['max'])) {
                $params['body']['query']['bool']['must'][]['range']['product_price']['gte'] = $price['min'];
                $params['body']['query']['bool']['must'][]['range']['product_price']['lte'] = $price['max'];
            } else {
                // Nếu chỉ có giá tối đa được chỉ định, tìm các sản phẩm có giá chính xác bằng giá đó
                $params['body']['query']['bool']['must'][]['match']['product_price'] = $price;
            }
        }

        if ($request->has('product_name')) {
            $params['body']['query']['bool']['must'][]['match']['product_name'] = $request->input('product_name');
        }

        if ($request->has('category_name')) {
            $params['body']['query']['bool']['must'][] = [
                'nested' => [
                    'path' => 'categories',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['match' => ['categories.name' => $request->input('category_name')]]
                            ]
                        ]
                    ]
                ]
            ];
        }

        if ($request->has('product_status')) {
            $params['body']['query']['bool']['must'][]['match']['product_status'] = $request->input('product_status');
        }
        // Lấy tất cả các thuộc tính được gửi trong request
        $attributes = $request->except(['brand_name', 'product_price', 'product_name', 'category_name', 'product_status']);

        // Tạo một mảng chứa điều kiện cho tất cả các thuộc tính
        $nestedQueries = [];
        foreach ($attributes as $attributeName => $attributeValue) {
            $nestedQueries[] = [
                'nested' => [
                    'path' => 'variations',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['match' => ['variations.attributes.' . $attributeName => $attributeValue]]
                            ]
                        ]
                    ]
                ]
            ];
        }

        // Thêm một điều kiện bool must với tất cả các điều kiện nested đã tạo
        $params['body']['query']['bool']['must'][] = [
            'bool' => [
                'must' => $nestedQueries
            ]
        ];

        // Khởi tạo Elasticsearch client
        $response = $elasticModel->getClientBuilder()->index($params);

        if (empty($response['hits']['hits'])) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu tương ứng.'], Response::HTTP_NOT_FOUND);
        }

        // Xử lý kết quả
        $products = $response['hits']['hits'];

        return response()->json($products);
    }

    public function index(Request $request)
    {
        // Lấy tham số page và per_page từ request (nếu không có thì mặc định là page 1 và 10 sản phẩm mỗi trang)
        $perPage = $request->input('per_page', 10);

        // Tạo một query builder
        $query = Product::with(['brand', 'category', 'images', 'variations']);

        // Kiểm tra nếu có tham số category_id
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Kiểm tra nếu có tham số brand_id
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        // Kiểm tra nếu có tham số slug
        if ($request->has('slug')) {
            $query->where('product_slug', $request->input('slug'));
        }

        // Kiểm tra nếu có tham số product_status
        $hasProductStatus = $request->has('product_status');
        if ($hasProductStatus) {
            $query->where('product_status', $request->input('product_status'));
        }

        // Nếu không có tham số product-list-pos và product_status không được chỉ định, thêm điều kiện kiểm tra status khác inactive
        if (!$request->has('product-list-pos') && !$hasProductStatus) {
            $query->where('product_status', '!=', 'inactive');
        }
        // Phân trang kết quả
        $products = $query->paginate($perPage);

        // Biến đổi sản phẩm để thêm thuộc tính
        $transformedProducts = $products->getCollection()->map(function ($product) {
            $attributes = [];

            // Đảm bảo variations là một mảng hoặc một đối tượng
            if (!empty($product->variations) && (is_array($product->variations) || is_object($product->variations))) {
                // Lặp qua mỗi biến thể để thu thập giá trị thuộc tính
                foreach ($product->variations as $variation) {
                    // Đảm bảo attributes là một mảng hoặc một đối tượng
                    if (!empty($variation->attributes) && (is_array($variation->attributes) || is_object($variation->attributes))) {
                        foreach ($variation->attributes as $key => $value) {
                            if (!isset($attributes[$key])) {
                                $attributes[$key] = [];
                            }
                            if (!in_array($value, $attributes[$key])) {
                                $attributes[$key][] = $value;
                            }
                        }
                    }
                }
            }

            // Thêm mảng thuộc tính vào sản phẩm
            $product->attributes = $attributes;

            return $product;
        });

        // Tạo response với dữ liệu phân trang
        return response()->json([
            'data' => [
                'products' => $transformedProducts->toArray(),
                'pagination' => [
                    'total_product' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem()
                ]
            ]
        ], Response::HTTP_OK);
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
            'product_price_import' => 'required|numeric',
            'commission_percentage' => 'nullable|numeric',
            'category_id' => 'required|exists:categories,id',
            // 'brand_id' => 'required|exists:brands,id',
            // 'product_thumbbail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation for product thumbnail
            // 'product_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'url'], // Allow either file upload or URL
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

        $data = $request->except(['image_detail', 'variations', 'image_detail_url', 'product_thumbbail_url']);
        // Upload and save product thumbnail
        $imageUrl = $this->uploadService->updateSingleImage($request, 'product_thumbbail', 'product_thumbbail_url', 'product_thumbnails', false);
        if (!empty($imageUrl)) {
            $data['product_thumbbail'] = $imageUrl;
        }
        // else {
        //     if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
        //         return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
        //     }
        // }
        $product = Product::create($data);
        $image_detail = $this->uploadService->uploadMultipleImages($request, 'image_detail', 'image_detail_url', 'product_images', false);
        // $filtered_array = array_filter($image_detail, function($value) {
        //     return $value !== null;
        // });
        // dd( $image_detail);
        if (!empty($image_detail)) {
            $saved_images = [];
            foreach ($image_detail as $image_path) {
                $product_image = new ProductImagesModel();
                $product_image->product_id = $product->id;
                $product_image->image_path = $image_path;
                $product_image->save();
                $saved_images[] = $product_image->image_path;
            }
        }
        //  else {
        //     if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
        //         return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
        //     }
        // }


        $variationsData = []; // Mảng để lưu trữ thông tin biến thể
        if ($request->has('variations')) {
            foreach ($request->variations as $variation) {
                // Đảm bảo dữ liệu biến thể không trống trước khi lưu
                if (!empty($variation)) {
                    // Sử dụng mô hình ProductVariation để lưu trữ thông tin vào bảng khác
                    $variationModel  = ProductVariation::create([
                        'product_id' => $product->id, // Hoặc bất kỳ khóa ngoại nào kết nối với sản phẩm
                        // 'size' => $variation['size'] ?? null,
                        // 'color' => $variation['color'] ?? null,
                        'attributes' => $variation['attributes'] ?? null, // Store attributes in a single JSON column

                        'price' => $variation['price'] ?? null,
                        'quantity' => $variation['quantity'] ?? null,
                    ]);
                    $variationsData[] = $variationModel;
                }
            }
        }
        $product = Product::with('brand', 'category', 'images', 'variations')->find($product->id);
        $attributes = [];

        // Ensure variations is an array or an object
        if (!empty($product->variations) && (is_array($product->variations) || is_object($product->variations))) {
            // Loop through each variation to collect attribute values
            foreach ($product->variations as $variation) {
                // Ensure attributes is an array or an object
                if (!empty($variation->attributes) && (is_array($variation->attributes) || is_object($variation->attributes))) {
                    foreach ($variation->attributes as $key => $value) {
                        if (!isset($attributes[$key])) {
                            $attributes[$key] = [];
                        }
                        if (!in_array($value, $attributes[$key])) {
                            $attributes[$key][] = $value;
                        }
                    }
                }
            }
        }

        // Add the attributes array to the product
        $product->attributes = $attributes;

        return response()->json([
            'message' => 'New product added successfully.',
            'data' => [
                'product' => $product,
                'variations' => $variationsData,
            ]
        ], Response::HTTP_CREATED);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['brand', 'category', 'images', 'variations'])->find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $attributes = [];

        // Ensure variations is an array or an object
        if (!empty($product->variations) && (is_array($product->variations) || is_object($product->variations))) {
            // Loop through each variation to collect attribute values
            foreach ($product->variations as $variation) {
                // Ensure attributes is an array or an object
                if (!empty($variation->attributes) && (is_array($variation->attributes) || is_object($variation->attributes))) {
                    foreach ($variation->attributes as $key => $value) {
                        if (!isset($attributes[$key])) {
                            $attributes[$key] = [];
                        }
                        if (!in_array($value, $attributes[$key])) {
                            $attributes[$key][] = $value;
                        }
                    }
                }
            }
        }

        // Add the attributes array to the product
        $product->attributes = $attributes;

        // Trả về dữ liệu JSON
        return response()->json(['data' => ['product' =>  $product->toArray()]], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */


    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id); // Tìm sản phẩm cần cập nhật

        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:products,product_code,' . $id,
            'product_price' => 'required|numeric',
            'product_price_import' => 'required|numeric',
            'commission_percentage' => 'nullable|numeric',
            'category_id' => 'required|exists:categories,id',
            // 'brand_id' => 'required|exists:brands,id',
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

        $data = $request->except(['image_detail', 'variations', 'image_detail_url', 'product_thumbbail_url']);
        $imageUrl = $this->uploadService->updateSingleImage($request, 'product_thumbbail', 'product_thumbbail_url', 'product_thumbnails', false);
        // dd( $imageUrl);
        if (is_string($imageUrl) || is_null($imageUrl)) {
            $data['product_thumbbail'] = $imageUrl;
        }
        // if ($imageUrl->getStatusCode() === 400 && !$imageUrl->getData()->status) {
        //     return response()->json(['error' => $imageUrl->getData()->message], $imageUrl->getStatusCode());
        // }
        // Cập nhật thông tin sản phẩm
        $product->update($data);
        // Cập nhật hình ảnh chi tiết nếu có
        $image_detail = $this->uploadService->uploadMultipleImages($request, 'image_detail', 'image_detail_url', 'product_images', false);
        // dd( $image_detail);
        $saved_images = [];
        if (!empty($image_detail)) {
            foreach ($image_detail as $image_path) {
                $product_image = new ProductImagesModel();
                $product_image->product_id = $product->id;
                $product_image->image_path = $image_path;

                // dd( $product_image->product_id, $product_image->image_path, $product->id, $image_path);
                $product_image->save();
                $saved_images[] = $product_image->image_path;
            }
        }

        // Cập nhật thông tin biến thể nếu có
        if ($request->has('variations')) {
            $variationsData = [];
            foreach ($request->variations as $variation) {
                if (!empty($variation)) {
                    $variationModel = ProductVariation::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            // 'size' => $variation['size'] ?? null,
                            // 'color' => $variation['color'] ?? null,
                            'attributes' => $variation['attributes'] ?? null,
                            'price' => $variation['price'] ?? null,
                            'quantity' => $variation['quantity'] ?? null,
                        ]
                    );
                    $variationsData[] = $variationModel;
                }
            }
        }
        $product = Product::with('brand', 'category', 'images', 'variations')->find($product->id);
        $attributes = [];

        // Ensure variations is an array or an object
        if (!empty($product->variations) && (is_array($product->variations) || is_object($product->variations))) {
            // Loop through each variation to collect attribute values
            foreach ($product->variations as $variation) {
                // Ensure attributes is an array or an object
                if (!empty($variation->attributes) && (is_array($variation->attributes) || is_object($variation->attributes))) {
                    foreach ($variation->attributes as $key => $value) {
                        if (!isset($attributes[$key])) {
                            $attributes[$key] = [];
                        }
                        if (!in_array($value, $attributes[$key])) {
                            $attributes[$key][] = $value;
                        }
                    }
                }
            }
        }

        // Add the attributes array to the product
        $product->attributes = $attributes;

        // Trả về thông tin sản phẩm sau khi cập nhật thành công
        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => [
                'product' => $product,
                'variations' => isset($variationsData) ? $variationsData : null,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        $product_image = ProductImagesModel::where('product_id', $id)->get();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Xóa hình ảnh sản phẩm
        if ($product->product_thumbbail) {
            $imagePath = str_replace(asset(''), '', $product->product_thumbbail); // Lấy đường dẫn tương đối
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }

        // Xóa tất cả biến thể của sản phẩm
        $product->variations()->delete();
        $product->images()->delete();

        if ($product_image) {
            foreach ($product_image as $image) {
                if (!filter_var($image, FILTER_VALIDATE_URL)) {
                    $imagePath = str_replace(asset(''), '', $image->image_path); // Lấy đường dẫn tương đối
                    if (File::exists($imagePath)) {
                        File::delete($imagePath);
                    }
                }
            }
        }
        $elasticModel = new BaseElastic();
        $params = [
            'index' => 'products',
            'type' => '_doc',
            'id' => $id
        ];

        try {
            $response = $elasticModel->getClientBuilder()->delete($params);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete product from Elasticsearch', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully', 'elasticsearch' => $response], Response::HTTP_OK);
    }

    public function updateSpecialCommissionPercentage(Request $request)
    {
        $request->validate([
            'new_commission_percentage' => 'required|numeric|min:0',
        ]);

        $newCommissionPercentage = $request->input('new_commission_percentage');

        // Cập nhật tất cả các dòng trong bảng products, không cần điều kiện
        $affectedRows = Product::query()->update(['special_commission_percentage' => $newCommissionPercentage]);

        return response()->json([
            'message' => 'Special commission percentages updated successfully',
            'affectedRows' => $affectedRows
        ], 200);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $import = new ProductsImport();
            Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Products imported successfully.',
                'data' => $import->getData(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error importing products.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportProducts(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        return Excel::download(new ProductsExport($perPage, $page), 'products.xlsx');
    }
}
