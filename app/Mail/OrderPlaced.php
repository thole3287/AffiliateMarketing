<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $orderItems;

    public function __construct($order, $orderItems)
    {
        $this->order = $order;
        $this->orderItems = $orderItems;
    }

    public function build()
    {
        return $this->view('emails.orderPlaced')
                    ->with([
                        'order' => $this->order,
                        'orderItems' => $this->orderItems,
                    ]);
    }
}
