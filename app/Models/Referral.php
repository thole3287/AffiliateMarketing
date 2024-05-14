<?php

namespace App\Models;

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
        'commission_amount'
    ];
}
