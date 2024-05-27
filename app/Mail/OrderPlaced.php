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
    public $discount;
    public $subtotal;
    public $discountPercentage;

    public function __construct($order, $orderItems, $discount, $subtotal, $discountPercentage)
    {
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->discount = $discount;
        $this->subtotal = $subtotal;
        $this->discountPercentage = $discountPercentage;

    }

    public function build()
    {
        return $this->view('emails.orderPlaced')
                    ->with([
                        'order' => $this->order,
                        'orderItems' => $this->orderItems,
                        'discount' => $this->discount,
                        'subtotal' => $this->subtotal,
                        'discountPercentage'=> $this->discountPercentage,
                    ]);
    }
}
