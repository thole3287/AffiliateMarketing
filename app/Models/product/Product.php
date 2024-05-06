<?php

namespace App\Models\product;

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Elasticquent\ElasticquentTrait;

class Product extends Model
{
    use ElasticquentTrait;

    use HasFactory;
    protected $table = 'products';
    protected $guarded = [];

    public function setProductPriceAttribute($value)
    {
        $this->attributes['product_price'] = (float) $value;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImagesModel::class, 'product_id');
    }

    public function productOffer()
    {
        return $this->hasOne(ProductOffersModel::class, 'offer_product_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }
}
