<?php

namespace App\Jobs;

use App\Mail\OrderCancelled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;


class SendOrderCancelledEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $order;
    protected $userEmail;

    public function __construct(Order $order, $userEmail)
    {
        $this->order = $order;
        $this->userEmail = $userEmail;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->userEmail)->send(new OrderCancelled($this->order));
    }
}
