<?php
namespace App\Imports;

use App\Models\product\Product;
use App\Models\product\ProductImagesModel;
use App\Models\product\ProductVariation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ProductsImport implements ToModel, WithHeadingRow
{
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

        // Handle product images dynamically
        foreach ($row as $column => $value) {
            if (Str::startsWith($column, 'image_') && !empty($value)) {
                $product_image = new ProductImagesModel();
                $product_image->product_id = $product->id;
                $product_image->image_path = $value;
                $product_image->save();
            }
        }

        // Handle product variations dynamically
        $variationAttributes = [];
        $variationData = [];
        foreach ($row as $column => $value) {
            if (Str::startsWith($column, 'variation_attribute_name_')) {
                $index = Str::replaceFirst('variation_attribute_name_', '', $column);
                $variationAttributes[$index] = $value;
            } elseif (Str::startsWith($column, 'variation_')) {
                $variationData[$column] = $value;
            }
        }

        // Group variations by index
        $groupedVariations = [];
        foreach ($variationData as $column => $value) {
            preg_match('/variation_(\w+)_(\d+)/', $column, $matches);
            if (count($matches) == 3) {
                $field = $matches[1];
                $index = $matches[2];
                $groupedVariations[$index][$field] = $value;
            }
        }

        foreach ($groupedVariations as $index => $variation) {
            $attributes = [];
            foreach ($variationAttributes as $attributeIndex => $attributeName) {
                $attributeColumn = 'attribute_' . $attributeIndex;
                if (isset($variation[$attributeColumn])) {
                    $attributes[$attributeName] = $variation[$attributeColumn];
                }
            }

            if (!empty($attributes) && isset($variation['price']) && isset($variation['quantity'])) {
                ProductVariation::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attributes' => $attributes,
                    ],
                    [
                        'price' => $variation['price'],
                        'quantity' => $variation['quantity'],
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
