<?php

namespace App\Http\ViewComposers\Shop\Product;

use App\Models\Shop\Product\Product;
use Illuminate\View\View;

class DealWeekComposer{

    protected $products;

    public function __construct(Product $products){
        $this->products = $products;
    }

    public function compose(View $view){
        $view->with('products', $this->products->getProductsOfWeek());
    }
}