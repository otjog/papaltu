<?php

namespace App\Models\Shop\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JustBetter\PaginationWithHavings\PaginationWithHavings;
use App\Models\Settings;

class Product extends Model{

    use PaginationWithHavings;

    protected $fillable = ['brand_id', 'category_id', 'manufacturer_id', 'active', 'name', 'scu'];

    protected $settings;

    protected $price_id;

    protected $pagination;

    protected $today;

    public function __construct(array $attributes = []){
        parent::__construct($attributes);

        $settings = Settings::getInstance();

        $this->price_id = $settings->getParameter('components.shop.price.id');

        $this->pagination = $settings->getParameter('components.shop.pagination');

        $this->today = $settings->getParameter('today');

    }

    public function brands(){
        return $this->belongsToMany('App\Models\Shop\Product\Brand', 'product_has_brand')->withTimestamps();
    }

    public function images(){
        return $this->belongsToMany('App\Models\Shop\Product\Image', 'product_has_image')->withTimestamps();
    }

    public function category(){
        return $this->belongsTo(    'App\Models\Shop\Category\Category');
    }

    public function manufacturer(){
        return $this->belongsTo(    'App\Models\Shop\Product\Manufacturer');
    }

    public function prices(){
        return $this->belongsToMany('App\Models\Shop\Price\Price', 'product_has_price')->withPivot('value', 'currency_id')->withTimestamps();
    }

    public function discounts(){
        return $this->belongsToMany('App\Models\Shop\Price\Discount', 'product_has_discount')->withPivot('value')->withTimestamps();
    }

    public function parameters(){
        return $this->belongsToMany('App\Models\Shop\Parameter\Parameter', 'product_has_parameter', 'product_id', 'parameter_id')->withPivot('id', 'value')->withTimestamps();
}

    public function basket_parameters(){
        return $this->belongsToMany('App\Models\Shop\Parameter\Parameter', 'product_has_parameter', 'product_id', 'parameter_id')->withPivot('id', 'value')->withTimestamps();
    }

    public function baskets(){
        return $this->belongsToMany('App\Models\Shop\Order\Basket', 'shop_basket_has_product', 'product_id', 'basket_id')->withPivot('quantity', 'order_attributes')->withTimestamps();
    }

    /*******************************/

    public function getAllProducts(){
        return self::select(
            'products.id',
            'products.category_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.thumbnail',
            'products.unique'
        )
            ->orderBy('name')
            ->get();
    }

    public function getActiveProduct($id){

        $products = self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.thumbnail',
            'products.weight',
            'products.length',
            'products.width',
            'products.height',
            'prices.id                      as price_id',
            'prices.name                    as price_name',
            'product_has_price.value        as price_pivot_value',
            'currency.value                 as price_pivot_currencyValue',
            'currency.char_code             as price_pivot_currencyCode',
            'currency.id                    as price_pivot_currencyId',
            'discounts.id                   as discounts_id',
            'discounts.name                 as discounts_name',
            'discounts.type                 as discounts_type',
            'product_has_discount.value     as discounts_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',
            'categories.id                  as category_id',
            'categories.name                as category_name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )
            ->where('products.id', '=', $id)

            ->where('products.active', '=', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND******************/
            ->with('brands')

            /************PARAMETERS*************/
            ->with(['parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 0);
            }])
            ->with(['basket_parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 1);
            }])

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************CATEGORY***************/
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')

            ->get();

            if( isset($products[0])){
                $products = $this->addRelationCollections($products);

                return $products[0];
            }else{
                return null;
            }

    }

    public function getActiveProductsFromCategory($category_id){

        $products = self::select(
            'products.id',
            'products.manufacturer_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail',
            'prices.id                      as price_id',
            'prices.name                    as price_name',
            'product_has_price.value        as price_pivot_value',
            'product_has_price.value        as price_value',
            'currency.value                 as price_pivot_currencyValue',
            'currency.char_code             as price_pivot_currencyCode',
            'currency.id                    as price_pivot_currencyId',
            'discounts.id                   as discounts_id',
            'discounts.name                 as discounts_name',
            'discounts.type                 as discounts_type',
            'product_has_discount.value     as discounts_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )
            ->where('products.category_id', $category_id)

            ->where('products.active', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND******************/
            ->with('brands')

            /************PARAMETERS*************/
            ->with(['parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 0);
            }])
            ->with(['basket_parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 1);
            }])

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            ->orderBy('products.name');

            $products = $products->paginate($this->pagination);

        return $this->addRelationCollections($products);

    }

    public function getActiveProductsFromCategoryWithFilterParameters($category_id){

        $products =  self::select(
            'products.id',
            'product_has_price.value        as price_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )
            ->where('products.category_id', $category_id)

            ->where('products.active', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND******************/
            ->with('brands')

            /************PARAMETER***************/
            ->with('parameters')

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            ->orderBy('products.name')

            ->get();

        return $this->addRelationCollections($products);

    }

    public function getFilteredProducts($parameters){

        $products = self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail',
            'prices.id                      as price_id',
            'prices.name                    as price_name',
            'product_has_price.value        as price_pivot_value',
            'currency.value                 as price_pivot_currencyValue',
            'currency.char_code             as price_pivot_currencyCode',
            'currency.id                    as price_pivot_currencyId',
            'discounts.id                   as discounts_id',
            'discounts.name                 as discounts_name',
            'discounts.type                 as discounts_type',
            'product_has_discount.value     as discounts_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )

            ->where('products.active', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            ->when(isset($parameters['price']), function ($query) use ($parameters) {

                list($min, $max) = (explode('|', $parameters['price']));

                return $query->having('price_value', '>=', ($min))
                    ->having('price_value', '<=', ($max));

            })

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND*******************/
            ->when(isset($parameters['brand']), function ($query) use ($parameters) {
                return $query->whereHas('brands', function($query) use ($parameters) {
                    $query->whereIn('product_has_brand.brand_id', explode('|', $parameters['brand']));
                });
            })
            ->with('brands')

            /************PARAMETERS*************/
            ->with(['parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 0);
            }])
            ->with(['basket_parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 1);
            }])

            /************MANUFACTURER***********/
            ->when(isset($parameters['manufacturer']), function ($query) use ($parameters) {
                return $query->whereIn('products.manufacturer_id', explode('|', $parameters['manufacturer']));
            })
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************CATEGORY***************/
            ->when(isset($parameters['category']), function ($query) use ($parameters) {
                return $query->whereIn('products.category_id', explode('|', $parameters['category']));
            });

            /************PARAMETERS*************/
            foreach($parameters as $key => $parameter){

                if(strpos($key, 'p_') === 0){
                    $key = str_replace('p_', '', $key);

                    $products = $products->whereHas('parameters', function($query) use ($parameter, $key) {
                        $query->where('product_parameters.alias', '=', $key)
                            ->whereIn('product_has_parameter.value', explode('|', $parameter));
                    });
                }

            }


            $products = $products->with('parameters')

            ->orderBy('products.name')

            ->paginate($this->pagination);

        return $this->addRelationCollections($products);

    }

    public function getActiveProductsOfBrand($brand_id){

        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail',
            'prices.id                      as price_id',
            'prices.name                    as price_name',
            'product_has_price.value        as price_pivot_value',
            'currency.value                 as price_pivot_currencyValue',
            'currency.char_code             as price_pivot_currencyCode',
            'currency.id                    as price_pivot_currencyId',
            'discounts.id                   as discounts_id',
            'discounts.name                 as discounts_name',
            'discounts.type                 as discounts_type',
            'product_has_discount.value     as discounts_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )

            ->where('products.active', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND******************/
            ->with(['brands' => function ($query) use ($brand_id){
                $query->where('active', '=', 1)
                    ->where('brand_id', '=', $brand_id);
            }])

            /************PARAMETERS*************/
            ->with(['parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 0);
            }])
            ->with(['basket_parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 1);
            }])

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            ->orderBy('products.name')

            ->paginate($this->pagination);

        return $this->addRelationCollections($products);

    }

    public function getProductsById($idProducts){

        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail',
            'products.weight',
            'products.length',
            'products.width',
            'products.height',
            'prices.id                      as price_id',
            'prices.name                    as price_name',
            'product_has_price.value        as price_pivot_value',
            'currency.value                 as price_pivot_currencyValue',
            'currency.char_code             as price_pivot_currencyCode',
            'currency.id                    as price_pivot_currencyId',
            'discounts.id                   as discounts_id',
            'discounts.name                 as discounts_name',
            'discounts.type                 as discounts_type',
            'product_has_discount.value     as discounts_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )
            ->whereIn('products.id', $idProducts)

            ->where('products.active', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND******************/
            ->with('brands')

            /************PARAMETERS*************/
            ->with(['parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 0);
            }])
            ->with(['basket_parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 1);
            }])

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            ->orderBy('products.name')

            ->paginate($this->pagination);

        return $this->addRelationCollections($products);

    }

    public function getProductsByIdFromBasket($idProducts){

        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail',
            'products.weight',
            'products.length',
            'products.width',
            'products.height',
            'prices.id                      as price_id',
            'prices.name                    as price_name',
            'product_has_price.value        as price_pivot_value',
            'currency.value                 as price_pivot_currencyValue',
            'currency.char_code             as price_pivot_currencyCode',
            'currency.id                    as price_pivot_currencyId',
            'discounts.id                   as discounts_id',
            'discounts.name                 as discounts_name',
            'discounts.type                 as discounts_type',
            'product_has_discount.value     as discounts_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',
            'product_parameters.alias       as product_parameters_alias',
            'product_parameters.name        as product_parameters_name',
            'product_parameters.order_attr  as product_parameters_order_attr',
            'product_has_parameter.value    as product_parameters_value',


            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )
            ->whereIn('products.id', $idProducts)

            ->where('products.active', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************PARAMETERS*************/
            ->leftJoin('product_has_parameter', function ($join) {
                $join->on('products.id', '=', 'product_has_parameter.product_id')
                    ->whereIn('product_has_parameter.value', ['40', '42', '46']);
            })
            ->leftJoin('product_parameters', function($join){
                $join->on('product_parameters.id', '=', 'product_has_parameter.parameter_id')
                    ->where('product_parameters.order_attr', '=', '1');
            })

            ->orderBy('products.name')

            ->get();

        return $this->addRelationCollections($products);

    }

    public function getProductsFromBasket($basket_id){

        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail',
            'products.weight',
            'products.length',
            'products.width',
            'products.height',
            'prices.id                      as price_id',
            'prices.name                    as price_name',
            'product_has_price.value        as price_pivot_value',
            'currency.value                 as price_pivot_currencyValue',
            'currency.char_code             as price_pivot_currencyCode',
            'currency.id                    as price_pivot_currencyId',
            'discounts.id                   as discounts_id',
            'discounts.name                 as discounts_name',
            'discounts.type                 as discounts_type',
            'product_has_discount.value     as discounts_pivot_value',
            'manufacturers.id               as manufacturer_id',
            'manufacturers.name             as manufacturer_name',
            'shop_baskets.id                            as baskets_id',
            'shop_baskets.token                         as baskets_token',
            'shop_basket_has_product.id                 as baskets_id',
            'shop_basket_has_product.basket_id          as baskets_pivot_basketId',
            'shop_basket_has_product.product_id         as baskets_pivot_productId',
            'shop_basket_has_product.quantity           as baskets_pivot_quantity',
            'shop_basket_has_product.order_attributes   as baskets_pivot_order_attributes',


            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS price_value'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS price_sale'
            )
        )

            ->where('products.active', 1)

            /************PRICE*******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('products.id', '=', 'product_has_price.product_id')
                    ->where('product_has_price.active', '=', '1')
                    ->where('product_has_price.price_id', '=', $this->price_id);
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->today);
            })

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************IN_BASKET**************/
            ->join('shop_basket_has_product', function ($join) use ($basket_id) {
                $join->on('products.id', '=', 'shop_basket_has_product.product_id')
                    ->where('shop_basket_has_product.basket_id', $basket_id);
            })
            ->join('shop_baskets', 'shop_baskets.id', '=', 'shop_basket_has_product.basket_id')


            /************PARAMETERS*************/
            ->with(['basket_parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 1);
            }])

            ->orderBy('shop_basket_has_product.id')

            ->get();

        return $this->addRelationCollections($products);

    }

    public function getProductsFromJson($json_string){

        $jsonArray = json_decode($json_string, true);

        $id_products = [];

        foreach($jsonArray as ["id" => $productId]){
            $id_products[] = $productId;
        }

        $productsCollect = $this->getProductsByIdFromBasket($id_products);

/*
        $productsCollect = $productsCollect->keyBy('id');

        foreach($jsonArray as ["id" => $productId, "quantity" => $productQuantity]){

            $productsCollect[ $productId ]->quantity = $productQuantity;
        }
*/


        foreach($jsonArray as $index => $product){

            foreach ($product as $key => $value){

                if( $key !== 'quantity' ){
/*
                    if( $product[ $key ] !== $newProduct[ $key ] ){

                        $is_new = true;

                    }
*/
                }

            }

        }

        return $productsCollect;

    }

    public function getTotal($products, $columnName = 'value'){

        $total = 0;

        foreach($products as $product){

            $total += $product->baskets['pivot']['quantity'] * $product->price[ $columnName ];

        }

        return $total;

    }

    public function getParcelParameters($products){

        $parameters = [];

        foreach($products as $key=>$product){

            $parameters[] = $this->getParametersForParcel($product);

        }

        return $parameters;
    }

    private function getParametersForParcel($product, $quantity = 1){

        if( isset($product->baskets['pivot']['quantity']) ){
            $quantity = $product->baskets['pivot']['quantity'];
        }

        return [
            'weight'    => $product['weight'],
            'length'    => $product['length'],
            'width'     => $product['width'],
            'height'    => $product['height'],
            'quantity'  => $quantity,

        ];
    }

    private function addRelationCollections($products){

        foreach ( $products as $product){

            foreach ($product->original as $key => $value){

                $match = explode('_', $key, 3);

                switch($match[0]){

                    case 'price'       :
                    case 'category'    :
                    case 'discounts'   :
                    case 'manufacturer':
                    case 'parameters':
                    case 'baskets':

                        if( !isset($data[ $match[0] ]) ){
                            $data[ $match[0] ] = [] ;
                        }

                        switch( $match[1] ){
                            case 'pivot'    : $data[ $match[0] ] [ $match[1] ] [ $match[2] ] = $value; break;
                            default         : $data[ $match[0] ] [ $match[1] ] = $value; break;
                        }
                        $product->relations[ $match[0] ] = collect($data[ $match[0] ]);

                        break;
                }

            }

        }

        return $products;
    }

}
