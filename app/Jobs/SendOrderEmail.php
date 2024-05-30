<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedMail;

class SendOrderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $order;
    protected $orderItems;
    protected $email;
    public $discount;
    public $subtotal;
    public $discountPercentage;

    /**
     * Create a new job instance.
     */
    public function __construct($order, $orderItems, $email, $discount, $subtotal, $discountPercentage)
    {
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->email = $email;
        $this->discount = $discount;
        $this->subtotal = $subtotal;
        $this->discountPercentage = $discountPercentage;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->email)->send(new OrderPlacedMail($this->order, $this->orderItems, $this->discount, $this->subtotal, $this->discountPercentage));
    }
}
