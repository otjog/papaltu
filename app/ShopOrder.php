<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopOrder extends Model{

    protected $fillable = [
        'ordered',
        'shop_basket_id',
        'payment_id',
        'shipment_id',
        'products',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'phone',
        'email',
        'address',
        'comment'
    ];

    public function getOrder($token){
        return self::select(
            'id',
            'ordered',
            'shop_basket_id',
            'payment_id',
            'shipment_id',
            'products',
            'first_name',
            'middle_name',
            'last_name',
            'phone',
            'email',
            'address',
            'comment'
            )
            ->where('token', $token)
            ->first();
    }

    public function getOrderById($id){
        return self::select(
            'id',
            'ordered',
            'shop_basket_id',
            'payment_id',
            'shipment_id',
            'products',
            'first_name',
            'middle_name',
            'last_name',
            'phone',
            'email',
            'address',
            'comment',
            'created_at'
        )
            ->where('id', $id)
            ->first();
    }

    public function getOrderByBasketIdAndOrderId($id){

        list($basketId, $orderId) = explode('-', $id);

        return self::select(
            'id',
            'ordered',
            'shop_basket_id',
            'payment_id',
            'shipment_id',
            'products',
            'first_name',
            'middle_name',
            'last_name',
            'phone',
            'email',
            'address',
            'comment',
            'created_at'
        )
            ->where('id', $orderId)

            ->where('shop_basket_id', $basketId)

            ->where('ordered', 1)

            ->first();
    }

}
