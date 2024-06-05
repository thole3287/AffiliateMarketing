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
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    private $data = [];

    public function model(array $row)
    {
        // Update or create the product
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
                'product_thumbbail' => $row['product_thumbbail'],
                'product_slug' => $row['product_slug'],
                'product_quantity' => $row['product_quantity'],
                'product_short_description' => $row['product_short_description'],
                'product_long_description' => $row['product_long_description'],
            ]
        );

        // Handle product images
        for ($i = 1; $i <= 10; $i++) { // Assume a maximum of 10 images for example
            $imageColumn = 'image_' . $i;

            if (isset($row[$imageColumn]) && !empty($row[$imageColumn])) {
                $product_image = new ProductImagesModel();
                $product_image->product_id = $product->id;
                $product_image->image_path = $row[$imageColumn];
                $product_image->save();
            }
        }

        // Handle product variations
        for ($i = 1; $i <= 10; $i++) { // Assume a maximum of 10 variations for example
            $sizeColumn = 'variation_size_' . $i;
            $colorColumn = 'variation_color_' . $i;
            $priceColumn = 'variation_price_' . $i;
            $quantityColumn = 'variation_quantity_' . $i;

            if (isset($row[$sizeColumn]) && isset($row[$colorColumn]) && isset($row[$priceColumn]) && isset($row[$quantityColumn])) {
                ProductVariation::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attributes' => ['size' => $row[$sizeColumn], 'color' => $row[$colorColumn]],
                    ],
                    [
                        'attributes' => ['size' => $row[$sizeColumn], 'color' => $row[$colorColumn]],
                        'price' => $row[$priceColumn],
                        'quantity' => $row[$quantityColumn],
                    ]
                );
            }
        }

        $this->data[] = $product; // Store the imported product in the data array

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
            $productData['images'] = $product->images->toArray();
            $productData['variations'] = $product->variations->toArray();
            $data[] = $productData;
        }

        return $data;
    }
}
