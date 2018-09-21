<?php

namespace App\Models\Shop\Order;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model{

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

    public function getMethodById($id){
        return self::select(
            'id',
            'alias',
            'name',
            'description',
            'img'
        )
            ->where('active', 1)
            ->where('id', $id)
            ->get();
    }

}
