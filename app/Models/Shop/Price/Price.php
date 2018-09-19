<?php

namespace App\Models\Shop\Price;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = ['name'];

    public function products(){
        return $this->belongsToMany('App\Models\Shop\Product\Product', 'product_has_price')->withPivot('value', 'currency_id')->withTimestamps();
    }

    public function getPriceProducts($price_name){
        return self::select(
            'product_has_price.active',
            'product_has_price.product_id',
            'product_has_price.value',
            'currency.value as quotation'
        )
            ->leftJoin('product_has_price', function ($join) {
                $join->on('product_has_price.price_id', '=', 'prices.id')
                    ->where('product_has_price.active', 1);
            })
            ->leftJoin('currency', function ($join) {
                $join->on('currency.char_code', '=', 'prices.currency');
            })
            ->where('prices.name', $price_name)
            ->get();
    }

}
