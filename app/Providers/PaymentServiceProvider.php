<?php

namespace App\Providers;

use App\Libraries\Services\Pay\Paymaster;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(){

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(){
        $this->app->bind('App\Libraries\Services\Pay\Contracts\OnlinePayment', function ($app) {
            return new Paymaster();
        });
    }
}
