<?php

namespace App\Models\Shop\Offer;

use Illuminate\Database\Eloquent\Model;
use App\Models\Shop\Product\Product;

class Offer extends Model{

    protected $table = 'shop_offers';

    public function products(){
        return $this->belongsToMany('App\Models\Shop\Product\Product', 'shop_offer_has_product', 'offer_id', 'product_id')
            ->withTimestamps();
    }

    public function getProductsOffer($take = 100){
        $offers = $this->getActiveOffers();
        $products = new Product();

        foreach($offers as $offer){

            if($offer->related){
                $offerProducts = $products->getCustomProductsOffer($offer->id, $take);

                $offer->relations = ['products' => $offerProducts];

            }else{
                $offerProducts = $products->getProductsPrepareOffer($offer->name, $take);

                $offer->relations = ['products' => $offerProducts];
            }
        }

        return $offers;
    }

    public function getSliceProductOffer($take = 6, $mainOfferName = 'deal-week'){

        $offers = $this->getProductsOffer($take);

        $mainOffer = $offers->first(function ($value, $key) use ($mainOfferName, $offers) {
            if($value->name === $mainOfferName){
                $offers->forget($key);
            }
            return $value->name === $mainOfferName;
        });

        return ['mainOffer' => $mainOffer, 'offers' => $offers];

    }

    //TODO поле name сделать уникальным
    public function getActiveOffers(){
        return self::select(
            'id',
            'name',
            'header',
            'related'
        )
            ->where('active', 1)
            ->get();
    }

}
