<?php

namespace App\Models\product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOffersModel extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'product_offer';
    public $timestamps = false;
}
