<?php

namespace App\Console\Commands;

use App\Elasticsearch\BaseElastic;
use App\Models\Brand;
use App\Models\Category;
use App\Models\product\Product;
use Exception;
use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;


class SyncElasticsearchDataProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-elasticsearch-data-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from MySQL to Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = 50; // Define the batch size
        $productsQuery = Product::where('sync_es', 'no');
        $totalProducts = $productsQuery->count();
        $batches = ceil($totalProducts / $batchSize);

        $elasticModel = new BaseElastic();

        for ($i = 0; $i < $batches; $i++) {
            $products = $productsQuery->skip($i * $batchSize)->take($batchSize)->get();

            foreach ($products as $product) {
                $variations = $product->variations()->where('sync_es', 'no')->get();

                // Fetch the brand using brand_id
                $brand = Brand::where('id', $product->brand_id)->where('sync_es', 'no')->first();
                $brands = $brand ? collect([$brand]) : collect();

                // Fetch the category using category_id
                $category = Category::where('id', $product->category_id)->where('sync_es', 'no')->first();
                $categories = $category ? collect([$category]) : collect();

                // Prepare the product data with variations, brands, and categories embedded as objects
                $productData = [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'product_code' => $product->product_code,
                    'product_tags' => $product->product_tags,
                    'product_colors' => $product->product_colors,
                    'product_short_description' => $product->product_short_description,
                    'product_long_description' => $product->product_long_description,
                    'product_slug' => $product->product_slug,
                    'product_price' => $product->product_price,
                    'product_price_import' => $product->product_price_import,
                    'product_thumbbail' => $product->product_thumbbail,
                    'product_status' => $product->product_status,
                    'commission_percentage' => $product->commission_percentage,
                    'category_id' => $product->category_id,
                    'categories' => $categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'parent_id' => $category->parent_id,
                            'image' => $category->image,
                            'sync_es' => $category->sync_es,
                            'status' => $category->status,
                            'created_at' => $category->created_at,
                            'updated_at' => $category->updated_at,
                        ];
                    })->toArray(),
                    'brand_id' => $product->brand_id,
                    'brands' => $brands->map(function ($brand) {
                        return [
                            'id' => $brand->id,
                            'name' => $brand->name,
                            'slug' => $brand->slug,
                            'image' => $brand->image,
                            'sync_es' => $brand->sync_es,
                            'status' => $brand->status,
                            'created_at' => $brand->created_at,
                            'updated_at' => $brand->updated_at,
                        ];
                    })->toArray(),
                    'vendor_id' => $product->vendor_id,
                    'product_quantity' => $product->product_quantity,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'variations' => $variations->map(function ($variation) {
                        $attributes = $variation->attributes;
                        if (is_string($attributes)) {
                            $attributes = json_decode($attributes, true);
                        }

                        return [
                            'id' => $variation->id,
                            'product_id' => $variation->product_id,
                            'attributes' => $attributes,
                            'price' => $variation->price,
                            'quantity' => $variation->quantity,
                            'created_at' => $variation->created_at,
                            'updated_at' => $variation->updated_at,
                        ];
                    })->toArray(),
                ];

                $params = [
                    'index' => 'products',
                    'type' => '_doc',
                    'id' => $product->id,
                    'body' => $productData,
                ];

                try {
                    $elasticModel->getClientBuilder()->index($params);

                    // Mark the product and related records as synced
                    $product->sync_es = 'yes';
                    $product->save();

                    foreach ($variations as $variation) {
                        $variation->sync_es = 'yes';
                        $variation->save();
                    }

                    foreach ($brands as $brand) {
                        $brand->sync_es = 'yes';
                        $brand->save();
                    }

                    foreach ($categories as $category) {
                        $category->sync_es = 'yes';
                        $category->save();
                    }

                    $this->info("Product ID {$product->id} synced successfully.");
                } catch (Exception $e) {
                    // Log error message
                    \Log::error("Error syncing Product ID {$product->id}: " . $e->getMessage());
                    $this->error("Error syncing Product ID {$product->id}. Check logs for details.");
                }

                // Introduce a small delay to prevent overwhelming Elasticsearch
                usleep(50000); // 50 milliseconds
            }
        }

        $this->info('Data synced to Elasticsearch successfully.');
    }


    // public function handle()
    // {
    //     $products = Product::where('sync_es', 'no')->get();
    //     $elasticModel = new BaseElastic();

    //     foreach ($products as $product) {

    //         $variations = $product->variations()->where('sync_es', 'no')->get();
    //         $brands = $product->brand()->where('sync_es', 'no')->get();
    //         $categories = $product->category()->where('sync_es', 'no')->get();
    //         // Prepare the product data with variations, brands, and categories embedded as objects
    //         $productData = [
    //             'id' => $product->id,
    //             'product_name' => $product->product_name,
    //             'product_code' => $product->product_code,
    //             'product_tags' => $product->product_tags,
    //             'product_colors' => $product->product_colors,
    //             'product_short_description' => $product->product_short_description,
    //             'product_long_description' => $product->product_long_description,
    //             'product_slug' => $product->product_slug,
    //             'product_price' => $product->product_price,
    //             'product_price_import' => $product->product_price_import,
    //             'product_thumbbail' => $product->product_thumbbail,
    //             'product_status' => $product->product_status,
    //             'commission_percentage' => $product->commission_percentage,
    //             'category_id' => $product->category_id,
    //             'categories' => $categories->map(function ($category) {
    //                 return [
    //                     'id' => $category->id,
    //                     'name' => $category->name,
    //                     'slug' => $category->slug,
    //                     'parent_id' => $category->parent_id,
    //                     'image' => $category->image,
    //                     'sync_es' => $category->sync_es,
    //                     'status' => $category->status,
    //                     'created_at' => $category->created_at,
    //                     'updated_at' => $category->updated_at,
    //                 ];
    //             })->toArray(),
    //             'brand_id' => $product->brand_id,
    //             'brands' => $brands->map(function ($brand) {
    //                 return [
    //                     'id' => $brand->id,
    //                     'name' => $brand->name,
    //                     'slug' => $brand->slug,
    //                     'image' => $brand->image,
    //                     'sync_es' => $brand->sync_es,
    //                     'status' => $brand->status,
    //                     'created_at' => $brand->created_at,
    //                     'updated_at' => $brand->updated_at,
    //                 ];
    //             })->toArray(),
    //             'vendor_id' => $product->vendor_id,
    //             'product_quantity' => $product->product_quantity,
    //             'created_at' => $product->created_at,
    //             'updated_at' => $product->updated_at,
    //             'variations' => $variations->map(function ($variation) {
    //                 $attributes = $variation->attributes;
    //                 if (is_string($attributes)) {
    //                     $attributes = json_decode($attributes, true);
    //                 }

    //                 return [
    //                     'id' => $variation->id,
    //                     'product_id' => $variation->product_id,
    //                     'attributes' => $attributes, // Ensure attributes is an object
    //                     'price' => $variation->price,
    //                     'quantity' => $variation->quantity,
    //                     'created_at' => $variation->created_at,
    //                     'updated_at' => $variation->updated_at,
    //                 ];
    //             })->toArray(),
    //         ];

    //         $params = [
    //             'index' => 'products',
    //             'type' => '_doc',
    //             'id' => $product->id,
    //             'body' => $productData,
    //         ];

    //         $elasticModel->getClientBuilder()->index($params);

    //         // Mark the product and related records as synced
    //         $product->sync_es = 'yes';
    //         $product->save();

    //         foreach ($variations as $variation) {
    //             $variation->sync_es = 'yes';
    //             $variation->save();
    //         }

    //         foreach ($brands as $brand) {
    //             $brand->sync_es = 'yes';
    //             $brand->save();
    //         }

    //         foreach ($categories as $category) {
    //             $category->sync_es = 'yes';
    //             $category->save();
    //         }
    //     }

    //     $this->info('Data synced to Elasticsearch successfully.');
    // }
}
