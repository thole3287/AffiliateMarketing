<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $orderItems;
    public $discount;
    public $subtotal;
    public $discountPercentage;

    /**
     * Create a new message instance.
     *
     * @param  mixed  $order
     * @param  string  $imagePath
     * @return void
     */
    public function __construct($order, $orderItems, $discount, $subtotal, $discountPercentage)
    {
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->discount = $discount;
        $this->subtotal = $subtotal;
        $this->discountPercentage = $discountPercentage;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Order Placed',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.orderPlaced',
            with: [
                'order' => $this->order,
                'orderItems' =>  $this->orderItems,
                'discount' => $this->discount,
                'subtotal' => $this->subtotal,
                'discountPercentage' => $this->discountPercentage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
