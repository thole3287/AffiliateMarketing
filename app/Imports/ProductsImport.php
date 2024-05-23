<?php

namespace App\Imports;

use App\Models\product\Product;
use App\Models\product\ProductImagesModel;
use App\Models\product\ProductVariation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    // use WithHeadingRow;
    private $data = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Xử lý dữ liệu từ mỗi dòng trong file Excel
        // Ví dụ: tạo hoặc cập nhật sản phẩm
        // dd( explode(',', $row['image_gellary']));
        $product = Product::updateOrCreate(
            [
                'product_code' => $row['product_code'],
            ],
            [
                'product_name' => $row['product_name'],
                'product_price' => $row['product_price'],
                'product_price_import' => $row['product_price_import'],
                'commission_percentage' => $row['commission_percentage'],
                'category_id' => $row['category_id'],
                'brand_id' => $row['brand_id'],
                'vendor_id' => $row['vendor_id'],
                'product_status' => $row['product_status'],
                'product_thumbbail' => $row['product_thumbbail'],
                'product_tags' => $row['product_tags'],
                'product_slug' => $row['product_slug'],
                'product_colors' => $row['product_colors'],
                'product_quantity' => $row['product_quantity'],
                'product_short_description' => $row['product_short_description'],
                'product_long_description' => $row['product_long_description'],

            ]
        );
        foreach (explode(',', $row['image_gellary']) as $image) {
                $product_image = new ProductImagesModel();
                $product_image->product_id = $product->id;
                $product_image->image_path = $image;
                $product_image->save();
        }
        // Xử lý biến thể sản phẩm (nếu có)
        if (isset($row['variations'])) {
            $variations = json_decode($row['variations'], true);
            foreach ($variations as $variation) {
                ProductVariation::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attributes' => $variation['attributes'],
                    ],
                    [
                        'price' => $variation['price'],
                        'quantity' => $variation['quantity'],
                    ]
                );
            }
        }

        $this->data[] = $product; // Lưu trữ sản phẩm đã import vào mảng data

        return $product;
    }

    /**
     * Get imported data.
     *
     * @return array
     */
    public function getData(): array
    {
        $data = [];

        foreach ($this->data as $product) {
            $productData = $product->toArray();
            $productData['variations'] = $product->variations->toArray();
            $data[] = $productData;
        }

        return $data;
    }
}
