<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message'
    ];

    public function ticket()
    {
        return $this->belongsTo(WithdrawalTicket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
