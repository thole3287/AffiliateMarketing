<?php

namespace App\Models\product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImagesModel extends Model
{
    use HasFactory;
    protected $table = 'brand';
    public $timestamps = false;
    protected $guarded = [];
}
