<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model{

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
}
