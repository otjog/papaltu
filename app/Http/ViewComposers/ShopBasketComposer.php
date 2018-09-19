<?php

namespace App\Http\ViewComposers;

use App\Models\Shop\Order\Basket;
use App\Models\Shop\Product\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopBasketComposer{

    protected $basket;

    public function __construct(Request $request, Basket $basket, Product $products){

        $token = $request->session()->get('_token');

        $this->basket = $basket->getActiveBasketWithProducts( $products, $token );
    }

    public function compose(View $view){
        $view->with('basket', $this->basket);
    }
}