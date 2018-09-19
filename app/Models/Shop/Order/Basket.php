<?php

namespace App\Models\Shop\Order;

use Illuminate\Database\Eloquent\Model;
use App\Models\Shop\Product\Product;

class Basket extends Model{

    protected $table = 'shop_baskets';

    public function shopOrder(){
        return $this->hasOne('App\Models\Shop\Order\Order', 'order_id');
    }

    public function getActiveBasket($token){
        return self::select('id', 'token','products_json', 'order_id')
            ->where('token', $token)
            ->where('order_id', null)
            ->first();
    }

    public function getActiveBasketWithProducts(Product $products, $token){
        $basket = self::select('id', 'token','products_json', 'order_id')
            ->where('token', $token)
            ->where('order_id', null)
            ->get();

        if( count( $basket ) > 0){

            $basket[0]->products = $products->getProductsFromJson($basket[0]->products_json);

            $basket[0]->total = $products->getTotal($basket[0]->products);

            $basket[0]->count_scu = count($basket[0]->products);

            return $basket[0];

        }else{
            return null;
        }

    }

}
