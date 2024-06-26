<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalTicket extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'amount',
        'bank_name',
        'account_number',
        'note',
        'status'
    ];
    protected $casts = [
        'amount' => 'float',
        'user_id' => 'int',
        'total_commission' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }
    public function userCommission()
    {
        return $this->belongsTo(UserCommission::class, 'user_id', 'user_id');
    }
}
