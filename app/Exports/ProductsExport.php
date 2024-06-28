<?php

namespace App\Exports;

use App\Models\product\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $perPage;
    protected $page;

    public function __construct($perPage = 10, $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
    }

    public function collection()
    {
        // Fetch products with pagination
        $offset = ($this->page - 1) * $this->perPage;
        return Product::with(['brand', 'category', 'images', 'variations'])
            ->offset($offset)
            ->limit($this->perPage)
            ->get();
    }

    public function headings(): array
    {
        // Define the headings for the Excel file
        $headings = [
            'Product Code', 'Product Name', 'Product Price', 'Product Price Import', 'Commission Percentage', 'Category', 'Brand',
            'Product Status', 'Product Thumbbail', 'Product Tags', 'Product Slug', 'Product Colors', 'Product Quantity',
            'Product Short Description', 'Product Long Description'
        ];

        // Add dynamic headings for images
        for ($i = 1; $i <= 10; $i++) {
            $headings[] = 'Image_' . $i;
        }

        // Add dynamic headings for variations
        for ($i = 1; $i <= 10; $i++) {
            $headings[] = 'Variation Size_' . $i;
            $headings[] = 'Variation Color_' . $i;
            $headings[] = 'Variation Price_' . $i;
            $headings[] = 'Variation Quantity_' . $i;
        }

        return $headings;
    }

    public function map($product): array
    {
        // Map product data to the appropriate columns
        $row = [
            $product->product_code, $product->product_name, $product->product_price, $product->product_price_import, $product->commission_percentage,
            $product->category->name ?? null, $product->brand->name ?? null, $product->product_status, $product->product_thumbbail, $product->product_tags,
            $product->product_slug, $product->product_colors, $product->product_quantity, $product->product_short_description, $product->product_long_description
        ];

        // Add images to the row
        for ($i = 0; $i < 10; $i++) {
            if (isset($product->images[$i])) {
                $row[] = $product->images[$i]->image_path;
            } else {
                $row[] = '';
            }
        }

        // Add variations to the row
        for ($i = 0; $i < 10; $i++) {
            if (isset($product->variations[$i])) {
                $variation = $product->variations[$i]->attributes;
                $row[] = $variation['size'] ?? null;
                $row[] = $variation['color'] ?? null;
                $row[] = $product->variations[$i]->price;
                $row[] = $product->variations[$i]->quantity;
            } else {
                $row[] = '';
                $row[] = '';
                $row[] = '';
                $row[] = '';
            }
        }

        return $row;
    }
}
