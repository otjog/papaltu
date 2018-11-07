<?php

namespace App\Http\ViewComposers\Shop\Product;

use App\Models\Shop\Offer\Offer;
use Illuminate\View\View;

class ShopOffersComposer{

    protected $offers;

    public function __construct(Offer $offers){
        $this->offers = $offers;
    }

    public function compose(View $view){
        $view->with('offers', $this->offers->getSliceProductOffer(6, 'deal-week'));
    }
}