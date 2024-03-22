<?php

namespace App\Models\product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;
    protected $table = "product_variations";
    protected $fillable = ['product_id', 'size', 'color', 'price', 'quantity'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
