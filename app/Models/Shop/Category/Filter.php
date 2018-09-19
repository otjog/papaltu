<?php

namespace App\Models\Shop\Category;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model{

    public function getActiveFilters( $request, $products){

        $filters =  self::select(
            'filters.id',
            'filters.alias',
            'filters.name',
            'filters.type'
        )
            ->where('filters.active', 1)
            ->orderBy('filters.sort')
            ->get();

        return $this->getParametersForFilters( $request, $products, $filters);
    }

    public function getParametersForFilters( $request, $products, $filters ){

        $temporary = [];
        $category_id    = $request->route()->parameters;
        $old_values     = $request->toArray();

        $productsInCategory = $products->getActiveProductsFromCategory($category_id);

        if( count($productsInCategory) > 0){
            foreach($filters as $filter){

                switch($filter['alias']){

                    case 'manufacturer' :
                        $manufacturers = $productsInCategory->pluck('manufacturer');
                        $filter['values']       = $manufacturers->pluck('name', 'id');
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;

                    case 'brand'        :
                        $brands = $productsInCategory->pluck('brands');
                        $filter['values']       = $brands->flatten()->pluck('name', 'id');
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;

                    case 'price'        :
                        $prices = $productsInCategory->pluck('prices');
                        $values = [
                            $prices->flatten()->min('value'),
                            $prices->flatten()->max('value'),
                        ];
                        $filter['values']       = $values;
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;

                    case 'phrase'       :
                        $filter['values']       = [];
                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);
                        break;

                    default             :
                        if($filter['filter_type'] === 'slider-range'){

                            //todo должно отдавать только минимальное и максимальное значение, как в price
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

    private function addOldValues($old_values, $filter_alias){
        if( isset( $old_values[ $filter_alias ] ) ){
            return explode('|', $old_values[ $filter_alias ]);
        }
        return [];
    }
}
