<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model{

    public function shopOrders(){
        return $this->hasMany('App\ShopOrder');
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
