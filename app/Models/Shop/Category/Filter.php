<?php

namespace App\Models\Shop\Category;

use http\Env\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shop\Product\Product;
use App\Models\Settings;

class Filter extends Model{

    protected $prefix;

    public function __construct(array $attributes = []){
        parent::__construct($attributes);

        $settings = Settings::getInstance();

        $this->prefix = $settings->getParameter('components.shop.filter_prefix');

    }

    public function getActiveFiltersWithParameters( $request, Product $products){

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

    public function getParametersForFilters( $request, Product $products, $filters ){

        $temporary = [];

        $category_id    = $request->route()->parameters;

        $old_values     = $request->toArray();

        $productsInCategory = $products->getActiveProductsFromCategoryWithFilterParameters($category_id);

        if( count($productsInCategory) > 0){

            foreach($filters as $key => $filter){

                switch($filter['alias']){

                    case 'manufacturer' :

                        /* если решим показывать в фильтре товары без производителя
                        $manufacturers = $productsInCategory->map(function ($value, $key){
                            $manufacturer = $value->relations['manufacturer'];

                             if($manufacturer['id'] !== null && $manufacturer['name'] !== null){
                                 return [ 'id' => $manufacturer['id'], 'name' => $manufacturer['name'] ];
                             }
                             return [ 'id' => 0, 'name' => 'Не задан' ];

                        });
*/

                        $manufacturers  = $productsInCategory->pluck('manufacturer');

                        $distinctManfs  = $manufacturers->pluck('name', 'id');

                        $filter['values']   = $distinctManfs->filter(function ($value, $key) {
                            return $key !== '' && $value !== null;
                        });

                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);

                        break;

                    case 'brand'        :

                        $brands = $productsInCategory->pluck('brands');

                        $filter['values']       = $brands->flatten()->pluck('name', 'id');

                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);

                        break;

                    case 'price'        :
                        $prices = $productsInCategory->pluck('price');

                        $values = [
                            $prices->min('value'),
                            $prices->max('value'),
                        ];

                        if($values[0] !== null || $values[1] !== null ){
                            $filter['values'] = $values;
                        }else{
                            $filter['values'] = [];
                        }

                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);

                        break;

                    case 'phrase'       :

                        $filter['values']       = [];

                        $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);

                        break;

                    default             :
                        if($filter['filter_type'] === 'slider-range'){

                            //todo должно отдавать только минимальное и максимальное значение, как в price
                            //todo проверка значений на null
                            $filter['values']       = [$filter['value']];

                            $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);

                            break;

                        }else{

                            $parameters = $productsInCategory->pluck('parameters');

                            $values = [];

                            foreach($parameters as $productParameters){

                                foreach($productParameters as $parameter){

                                    if( $parameter->alias === $filter['alias']){

                                        if( !isset($values[ $parameter->pivot->value ])){
                                            $values[ $parameter->pivot->value ] = $parameter->pivot->value;
                                        }

                                    }

                                }
                            }

                            $filter['alias']        = $this->prefix . $filter['alias'];

                            $filter['values']       = array_flip($values);

                            $filter['old_values']   = $this->addOldValues($old_values, $filter['alias']);

                            break;

                        }
                }


                if( count( $filter['values'] ) > 0){
                    $temporary[] = $filter;
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
