<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model{

    protected $fillable = ['name'];

    public function products(){
        return $this->belongsToMany('App\Product', 'product_has_brand')->withTimestamps();
    }

    public function getActiveBrands(){
        return self::select(
            'id',
            "name"
        )
            ->where('active', 1)
            ->get();

    }

    public function getActiveBrand($id){
        return self::select(
            'id',
            "name"
        )
            ->where('id', $id)
            ->where('active', 1)
            ->get();

    }

}
