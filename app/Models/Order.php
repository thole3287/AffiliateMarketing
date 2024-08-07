<?php

namespace App\Models;

use App\Models\product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shipping_address',
        'order_date',
        'total_amount',
        'payment_method',
        'payment_status',
        'note',
        'discount',
        'check_out_order',
        'commission_processed',
        'zalo_order_id'
    ];
    protected $casts = [
        'user_id' => 'int',
        'total_amount' => 'float',
        'discount' => 'float',
        'check_out_order' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->using(OrderItems::class)
            ->withPivot('quantity', 'variation_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'status_payment_updated_by');
    }
}
