<?php

namespace App\Models\Shop\Product;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model{

    protected $fillable = ['name'];

    public function products(){
        return $this->hasMany('App\Models\Shop\Product\Product');
    }

}
