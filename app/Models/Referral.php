<?php

namespace App\Models;

use App\Models\product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;
    protected $table = 'referrals';
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'total_amount',
        'commission_percentage',
        'commission_amount',
        'status'
    ];

    protected $casts = [
        'commission_percentage' => 'float',
        'commission_amount' => 'float',
        'user_id' => 'int',
        'product_id' => 'int',
        'order_id' => 'int',
    ];

     // Mối quan hệ với model User (người giới thiệu)
     public function user()
     {
         return $this->belongsTo(User::class);
     }

     // Mối quan hệ với model Product (sản phẩm được giới thiệu)
     public function product()
     {
         return $this->belongsTo(Product::class);
     }

     // Mối quan hệ với model Order (đơn hàng được tạo từ việc giới thiệu)
     public function order()
     {
         return $this->belongsTo(Order::class);
     }
}
