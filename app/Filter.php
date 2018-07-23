<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model{

    public function getActiveFilters($request, $products){

        $filters =  self::select(
            'filters.id',
            'filters.alias',
            'filters.name',
            'filters.type'
        )
            ->where('filters.active', 1)
            ->orderBy('filters.sort')
            ->get();

        return $this->getParametersForFilters($request, $products, $filters);
    }

    public function getParametersForFilters( $request, $products, $filters ){

        $temporary = [];
        $category_id    = $request->route()->parameters;
        $old_values     = $request->toArray();

        if( $products->checkActiveProductsInCategory( $category_id ) > 0){
            foreach($filters as $filter){

                switch($filter['alias']){
                    case 'manufacturer' :
                        $filter['values']       = array_column(self::getManufacturerFilter($category_id), 'value', 'id');
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;
                    case 'brand'        :
                        $filter['values']       = array_column(self::getBrandFilter($category_id), 'value', 'id');
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;
                    case 'price'        :
                        $filter['values']       = array_column(self::getPriceFilter($category_id, $price_id = 2), 'value');
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;
                    case 'phrase'       :
                        $filter['values']       = [];
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;
                    default             :
                        if($filter['filter_type'] === 'slider-range'){
                            $filter['values']       = [$filter['value']];
                            $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                            break;
                        }else{
                            $filter['values']       = [$filter['value_id'] => $filter['value']];
                            $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                            break;
                        }
                }

                if( count( $filter['values'] ) > 0){
                    $temporary[$filter['alias']] = $filter->toArray();
                }

            }
        }

        return $temporary;
    }

    private static function getManufacturerFilter($category_id){
        return Manufacturer::select('id', 'name as value')
            ->whereIn('id', Product::select('manufacturer_id')
                ->where('active', 1)
                ->where('category_id', $category_id)
                ->distinct()
                ->get()
            )
            ->get()->toArray();
    }

    private static function getBrandFilter($category_id){
        return Brand::select('brands.id', 'brands.name as value')
            ->join('product_has_brand', function($join){
                $join->on('product_has_brand.brand_id', '=', 'brands.id');
            })
            ->whereIn('product_id', Product::select('id')
                ->where('active', 1)
                ->where('category_id', $category_id)
                ->get()
            )
            ->get()->toArray();
    }

    private static function getPriceFilter($category_id, $price_id){
        return Price::select(
            'product_has_price.value'
        )
            ->join('product_has_price', function($join){
                $join->on('product_has_price.price_id', '=', 'prices.id');
            })
            ->where('product_has_price.active', 1)
            ->where('prices.id', $price_id)
            ->whereIn('product_id', Product::select('id')
                ->where('products.active', 1)
                ->where('category_id', $category_id)
                ->get()
            )
            ->orderBy('value')
            ->get()->toArray();
    }

    private function addOldValues($old_values, $filter_alias){
        if( isset( $old_values[ $filter_alias ] ) ){
            return explode('|', $old_values[ $filter_alias ]);
        }
        return [];
    }
}
