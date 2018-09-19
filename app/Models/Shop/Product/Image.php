<?php

namespace App\Models\Shop\Product;

use Illuminate\Database\Eloquent\Model;

class Image extends Model{

    public function products(){
        return $this->belongsToMany('App\Models\Shop\Product\Product', 'product_has_brand')->withTimestamps();
    }

    public function getAllImages(){
        return self::select(
            'images.id',
            'images.name',
            'images.src'
        )
            ->get();
    }

}
