<?php

namespace App\Models\Shop\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JustBetter\PaginationWithHavings\PaginationWithHavings;

class Product extends Model{

    use PaginationWithHavings;

    protected $fillable = ['brand_id', 'category_id', 'manufacturer_id', 'active', 'name', 'scu'];

    private $date;

    private $pagination = 15;

    public function __construct(array $attributes = []){
        parent::__construct($attributes);

        $this->date = date('Y-m-d');

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
                    ->where('product_has_price.active', '=', '1');
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->date);
            })

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************CATEGORY***************/
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')

            ->get();

            if( isset($products[0])){
                $products = $this->addRelationCollections($products);
                //dd($products[0]->name);
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
                    ->where('product_has_price.active', '=', '1');
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->date);
            })

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            ->orderBy('products.name');

            $products = $products->paginate($this->pagination);

        return $this->addRelationCollections($products);

    }

    public function getActiveProductsFromCategoryWithFilterParameters($category_id){

        $products =  self::select(
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
                    ->where('product_has_price.active', '=', '1');
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->date);
            })

            /************BRAND******************/
            ->with('brands')

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
                    ->where('product_has_price.active', '=', '1');
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
                    ->whereDate('to_date', '>=', $this->date);
            })


            /************BRAND*******************/
            ->when(isset($parameters['brand']), function ($query) use ($parameters) {
                return $query->whereHas('brands', function($query) use ($parameters) {
                    $query->whereIn('product_has_brand.brand_id', explode('|', $parameters['brand']));
                });
            })
            ->with('brands')

            /************MANUFACTURER***********/
            ->when(isset($parameters['manufacturer']), function ($query) use ($parameters) {
                return $query->whereIn('products.manufacturer_id', explode('|', $parameters['manufacturer']));
            })
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************CATEGORY***************/
            ->when(isset($parameters['category']), function ($query) use ($parameters) {
                return $query->whereIn('products.category_id', explode('|', $parameters['category']));
            })

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
                    ->where('product_has_price.active', '=', '1');
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->date);
            })

            /************BRAND******************/
            ->with(['brands' => function ($query) use ($brand_id){
                $query->where('active', '=', 1)
                    ->where('brand_id', '=', $brand_id);
            }])

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            ->orderBy('products.name')

            ->paginate($this->pagination);

        return $this->addRelationCollections($products);

    }

    public function getProductsById($idProducts, $paginate = true){

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
                    ->where('product_has_price.active', '=', '1');
            })
            ->leftJoin('prices','prices.id', '=', 'product_has_price.price_id')

            /************CURRENCY****************/
            ->leftJoin('currency', 'currency.id', '=', 'product_has_price.currency_id')

            /************DISCOUNT****************/
            ->leftJoin('product_has_discount', 'products.id', '=', 'product_has_discount.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('discounts.id', '=', 'product_has_discount.discount_id')
                    ->where('discounts.active', '=', '1')
                    ->whereDate('to_date', '>=', $this->date);
            })

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            ->orderBy('products.name');

            if($paginate){
                $products = $products->paginate($this->pagination);
            }else{
                $products = $products->get();
            }

        return $this->addRelationCollections($products);

    }

    public function getProductsFromJson($json_string){

        $jsonArray = json_decode($json_string, true);

        $id_products = [];

        foreach($jsonArray as ["id" => $productId]){
            $id_products[] = $productId;
        }

        $productsCollect = $this->getProductsById($id_products, $paginate = false);

        $productsCollect = $productsCollect->keyBy('id');

        foreach($jsonArray as ["id" => $productId, "quantity" => $productQuantity]){
            $productsCollect[ $productId ]->quantity = $productQuantity;
        }

        return $productsCollect;

    }

    public function getTotal($products, $columnName = 'value'){

        $total = 0;

        foreach($products as $product){

            $total += $product->quantity * $product->price[ $columnName ];

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

        if( isset($product['quantity']) ){
            $quantity = $product['quantity'];
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

                $match = explode('_', $key);

                switch($match[0]){

                    case 'price'       :
                    case 'category'    :
                    case 'discounts'   :
                    case 'manufacturer':

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
