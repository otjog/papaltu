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
        $template = env('SITE_TEMPLATE');
        View::composers([
            'App\Http\ViewComposers\CategoryMenuComposer'   => $template . '.modules.menu.shop',
            'App\Http\ViewComposers\PageMenuComposer'       => $template . '.modules.menu.page',
            'App\Http\ViewComposers\ShopBasketComposer'     => $template . '.modules.shop_basket.default',
            'App\Http\ViewComposers\ProductFilterComposer'  => $template . '.modules.product_filter.default',
            'App\Http\ViewComposers\BannerComposer'         => $template . '.modules.banner.default',
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
