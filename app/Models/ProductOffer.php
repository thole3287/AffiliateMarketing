<?php

namespace App\Models;

use App\Models\product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOffer extends Model
{
    use HasFactory;
    protected $table = 'product_offer';
    public function product() {
        return $this->belongsTo(Product::class, 'offer_product_id');
    }
}
