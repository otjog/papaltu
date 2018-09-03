<?php

namespace App\Listeners;

use App\Events\NewOrder;
use App\Mail\OrderShipped;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendMailNewOrder{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(){

    }

    /**
     * Handle the event.
     *
     * @param  NewOrder  $event
     * @return void
     */
    public function handle(NewOrder $event){
        /*
                $customer_email = $event->order->customer->email;

                $customer_name = $event->order->customer->full_name;

                Mail::to( $customer_email, $customer_name)
                    ->send(new OrderShipped(
                            [
                                'data'  => $event->order
                            ])
                    );
        */
        Mail::to(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
            ->send(new OrderShipped(
                    [
                        'data'  => $event->order
                    ])
            );
    }
}
