<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(){
        View::composers([
            'App\Http\ViewComposers\CategoryMenuComposer'   => 'modules.menu.shop',
            'App\Http\ViewComposers\PageMenuComposer'       => 'modules.menu.page',
            'App\Http\ViewComposers\ShopBasketComposer'     => 'modules.shop_basket.default',
            'App\Http\ViewComposers\ProductFilterComposer'  => 'modules.product_filter.default',
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(){
        //
    }
}
