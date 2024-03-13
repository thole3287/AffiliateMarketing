<?php

namespace App\Models\product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImagesModel extends Model
{
    use HasFactory;
    protected $table = 'product_images';
    public $timestamps = false;
    protected $guarded = [];

    public function product() {
        return $this->belongsTo(Product::class, 'image_product_id');
    }
}
