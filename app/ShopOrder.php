<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopOrder extends Model{

    protected $fillable = [
        'shop_basket_id',
        'payment_id',
        'shipment_id',
        'customer_id',
        'products_json',
        'address',
        'comment',
        'paid',
        'pay_id',
        'address_json'
    ];

    public function shopBasket(){
        return $this->belongsTo('App\ShopBasket', 'shop_basket_id');
    }

    public function shipment(){
        return $this->belongsTo('App\Shipment');
    }

    public function payment(){
        return $this->belongsTo('App\Payment');
    }

    public function customer(){
        return $this->belongsTo('App\Models\Shop\Customer');
    }

    public function getOrderById(Product $products, $id){
        $order = self::select(
            'id',
            'shop_basket_id',
            'payment_id',
            'shipment_id',
            'customer_id',
            'products_json',
            'address as delivery_address',
            'comment',
            'paid',
            'pay_id',
            'address_json as delivery_address_json',
            'created_at'
        )
            ->where('id', $id)

            /************CUSTOMER***********/
            ->with('customer')

            /************SHIPMENT***********/
            ->with('shipment')

            /************PAYMENT***********/
            ->with('payment')

            ->get();

        if( count( $order ) > 0){

            $order[0]->products = $products->getProductsFromJson($order[0]->products_json);

            $order[0]->total = $products->getTotal($order[0]->products);

            $order[0]->count_scu = count($order[0]->products);

            return $order[0];

        }else{
            return null;
        }
    }

    public function getOrderByPayId($payId){
        return self::select(
            'shop_basket_id',
            'payment_id',
            'shipment_id',
            'customer_id',
            'products_json',
            'address as delivery_address',
            'comment',
            'paid',
            'pay_id',
            'address_json as delivery_address_json'
        )
            ->where('pay_id', $payId)
            ->get();
    }

    public function getOrderByBasketIdAndOrderId($id){

        list($basketId, $orderId) = explode('-', $id);

        return self::select(
            'shop_basket_id',
            'payment_id',
            'shipment_id',
            'customer_id',
            'products_json',
            'address as delivery_address',
            'comment',
            'paid',
            'pay_id',
            'address_json as delivery_address_json'
        )
            ->where('id', $orderId)

            ->where('shop_basket_id', $basketId)

            ->where('ordered', 1)

            ->first();
    }

    public function storeOrder($data, $basket, $customer){

        $data_order = $this->getDataForOrder($data, $basket, $customer);

        $order = self::create($data_order);

        $order->relations['customer'] = $customer;

        $order->products =

        $basket->order_id = $order->id;

        $basket->save();

        return $order;
    }

    private function getDataForOrder($data, $basket, $customer){

        $data_order = [
            'shop_basket_id'    => $basket->id,
            'products_json'     => $basket->products_json,
            'customer_id'       => $customer->id,
        ];

        foreach($data as $key => $value){
            switch($key){
                case 'payment_id'       :
                case 'shipment_id'      :
                case 'address'          :
                case 'address_json'     :
                case 'comment'          :
                case 'paid'             :
                case 'pay_id'           : $data_order[$key] = $value;
            }
        }

        return $data_order;
    }

}