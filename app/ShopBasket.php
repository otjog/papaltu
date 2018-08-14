<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Product;

class ShopBasket extends Model{

    public function getBasket($token){
        return self::select('id', 'token','products', 'order_id')
            ->where('token', $token)
            ->first();
    }

    public function getActiveBasket($token){
        return self::select('id', 'token','products', 'order_id')
            ->where('token', $token)
            ->where('order_id', null)
            ->first();
    }

}
