<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model{

    public function getActiveMethods(){
        return self::select(
            'id',
            'name',
            'description',
            'img'
        )
            ->where('active', 1)
            ->get();
    }

}
