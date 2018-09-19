<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model{

    public function products(){
        return $this->belongsToMany('App\Product', 'product_has_discount')->withPivot('value')->withTimestamps();
    }

}
