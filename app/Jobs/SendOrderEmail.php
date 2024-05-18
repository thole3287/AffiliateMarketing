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

    /**
     * Create a new job instance.
     */
    public function __construct($order, $orderItems, $email)
    {
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->email)->send(new OrderPlacedMail($this->order, $this->orderItems));
    }
}
