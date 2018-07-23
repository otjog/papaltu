<?php

namespace App\Http\ViewComposers;

use App\ShopBasket;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopBasketComposer{

    protected $products;
    protected $basket;

    public function __construct(Request $request, ShopBasket $basket, Product $products){
        $this->products = $products;
        $this->basket   = $basket->getActiveBasket( $request->session()->get('_token') );
    }

    public function compose(View $view){
        $view->with('basket', $this->products->getProductsFromBasket($this->basket));
    }
}