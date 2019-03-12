<?php

namespace App\Models\Shop\Order;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model{

    public function shopOrders(){
        return $this->hasMany('App\Models\Shop\Order\Order');
    }

    public function getActiveMethods(){
        return self::select(
            'id',
            'alias',
            'name',
            'description',
            'img'
        )
            ->where('active', 1)
            ->get();
    }

    public function getDeliveryServices(){
        return self::select(
            'id',
            'alias',
            'name',
            'description',
            'img'
        )
            ->where('active', 1)
            ->where('is_service', 1)
            ->get();
    }


    public function getDefaultShipments(){
        return self::select(
            'id',
            'alias',
            'name',
            'description',
            'img'
        )
            ->whereIn('alias', ['self', 'delivery'])
            ->get();
    }

    public function getShipmentServiceByAlias($alias){
        return self::select(
            'id',
            'alias',
            'name',
            'description',
            'img'
        )
            ->where('active', 1)
            ->where('is_service', 1)
            ->where('alias', $alias)
            ->get();
    }

}
