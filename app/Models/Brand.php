<?php

namespace App\Models;

use App\Models\product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'image', 'status'];

    public function products() {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
