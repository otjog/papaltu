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

    public function orders(){
        return $this->belongsToMany('App\Models\Shop\Order\Order', 'shop_order_has_product', 'product_id', 'order_id')
            ->withPivot('quantity', 'price_id', 'currency_id', 'price_value', 'order_attributes')
            ->withTimestamps();
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
            'prices.id                      as price|id',
            'prices.name                    as price|name',
            'product_has_price.value        as price|pivot|value',
            'currency.value                 as price|pivot|currency_value',
            'currency.char_code             as price|pivot|currency_code',
            'currency.id                    as price|pivot|currency_id',
            'discounts.id                   as discounts|id',
            'discounts.name                 as discounts|name',
            'discounts.type                 as discounts|type',
            'product_has_discount.value     as discounts|pivot|value',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',
            'categories.id                  as category|id',
            'categories.name                as category|name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS "price|value"'
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS "price|sale"'
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
            'prices.id                      as price|id',
            'prices.name                    as price|name',
            'product_has_price.value        as price|pivot|value',
            'product_has_price.value        as price|value',
            'currency.value                 as price|pivot|currency_value',
            'currency.char_code             as price|pivot|currency_code',
            'currency.id                    as price|pivot|currency_id',
            'discounts.id                   as discounts|id',
            'discounts.name                 as discounts|name',
            'discounts.type                 as discounts|type',
            'product_has_discount.value     as discounts|pivot|value',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS "price|value" '
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS "price|sale" '
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
            'product_has_price.value        as price|pivot|value',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS "price|value" '
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS "price|sale" '
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
            'prices.id                      as price|id',
            'prices.name                    as price|name',
            'product_has_price.value        as price|pivot|value',
            'currency.value                 as price|pivot|currency_value',
            'currency.char_code             as price|pivot|currency_code',
            'currency.id                    as price|pivot|currency_id',
            'discounts.id                   as discounts|id',
            'discounts.name                 as discounts|name',
            'discounts.type                 as discounts|type',
            'product_has_discount.value     as discounts|pivot|value',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS "price|value" '
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS "price|sale" '
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

                return $query->having('price|value', '>=', ($min))
                    ->having('price|value', '<=', ($max));

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
            'prices.id                      as price|id',
            'prices.name                    as price|name',
            'product_has_price.value        as price|pivot|value',
            'currency.value                 as price|pivot|currency_value',
            'currency.char_code             as price|pivot|currency_code',
            'currency.id                    as price|pivot|currency_id',
            'discounts.id                   as discounts|id',
            'discounts.name                 as discounts|name',
            'discounts.type                 as discounts|type',
            'product_has_discount.value     as discounts|pivot|value',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS "price|value" '
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS "price|sale" '
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
            'prices.id                      as price|id',
            'prices.name                    as price|name',
            'product_has_price.value        as price|pivot|value',
            'currency.value                 as price|pivot|currency_value',
            'currency.char_code             as price|pivot|currency_code',
            'currency.id                    as price|pivot|currency_id',
            'discounts.id                   as discounts|id',
            'discounts.name                 as discounts|name',
            'discounts.type                 as discounts|type',
            'product_has_discount.value     as discounts|pivot|value',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',

            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS "price|value" '
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS "price|sale" '
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
            'prices.id                      as price|id',
            'prices.name                    as price|name',
            'product_has_price.value        as price|pivot|value',
            'currency.value                 as price|pivot|currency_value',
            'currency.char_code             as price|pivot|currency_code',
            'currency.id                    as price|pivot|currency_id',
            'discounts.id                   as discounts|id',
            'discounts.name                 as discounts|name',
            'discounts.type                 as discounts|type',
            'product_has_discount.value     as discounts|pivot|value',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',
            'shop_basket_has_product.basket_id          as pivot|basket_id',
            'shop_basket_has_product.product_id         as pivot|product_id',
            'shop_basket_has_product.quantity           as pivot|quantity',
            'shop_basket_has_product.order_attributes   as pivot|order_attributes',


            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( ( product_has_price.value - (product_has_price.value / 100 * product_has_discount.value) ) * currency.value )
                           WHEN "value"
                                    THEN ROUND( (product_has_price.value * currency.value) - product_has_discount.value )
                           ELSE ROUND( product_has_price.value * currency.value )
                        END AS "price|value" '
            ),
            DB::raw(
                'CASE discounts.type
                           WHEN "percent"
                                    THEN ROUND( product_has_price.value / 100 * product_has_discount.value * currency.value )
                           WHEN "value"
                                    THEN ROUND( product_has_discount.value * currency.value )
                           ELSE 0
                        END AS "price|sale" '
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

    public function getProductsFromOrder($order_id){

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
            'prices.id                      as price|id',
            'prices.name                    as price|name',
            'currency.value                 as price|pivot|currency_value',
            'currency.char_code             as price|pivot|currency_code',
            'currency.id                    as price|pivot|currency_id',
            'manufacturers.id               as manufacturer|id',
            'manufacturers.name             as manufacturer|name',
            'shop_order_has_product.order_id           as pivot|order_id',
            'shop_order_has_product.product_id         as pivot|product_id',
            'shop_order_has_product.quantity           as pivot|quantity',
            'shop_order_has_product.price_id           as pivot|price_id',
            'shop_order_has_product.currency_id        as pivot|currency_id',
            'shop_order_has_product.price_value        as price|value',
            'shop_order_has_product.order_attributes   as pivot|order_attributes'
        )

            /************IN_ORDER**************/
            ->join('shop_order_has_product', function ($join) use ($order_id) {
                $join->on('products.id', '=', 'shop_order_has_product.product_id')
                    ->where('shop_order_has_product.order_id', $order_id);
            })
            ->join('shop_orders', 'shop_orders.id', '=', 'shop_order_has_product.order_id')


            /************PRICE*******************/
            ->leftJoin('prices','prices.id', '=', 'shop_order_has_product.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'shop_order_has_product.currency_id')

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************PARAMETERS*************/
            ->with(['basket_parameters' => function ($query) {
                $query->where('product_parameters.order_attr', '=', 1);
            }])

            ->orderBy('shop_order_has_product.id')

            ->get();

        return $this->addRelationCollections($products);

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

                $match = explode('|', $key, 3);

                switch($match[0]){

                    case 'price'       :
                    case 'category'    :
                    case 'discounts'   :
                    case 'manufacturer':
                    case 'parameters':
                    case 'baskets':
                    case 'pivot':

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
