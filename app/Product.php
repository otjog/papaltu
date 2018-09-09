<?php

namespace App;

use App\Models\Shop\Currency;
use Illuminate\Database\Eloquent\Model;

class Product extends Model{

    protected $fillable = ['brand_id', 'category_id', 'manufacturer_id', 'active', 'name', 'scu'];

    private $currencies;

    public function __construct(array $attributes = []){
        parent::__construct($attributes);

        $this->currencies = new Currency();

    }

    public function brands(){
        return $this->belongsToMany('App\Brand', 'product_has_brand')->withTimestamps();
    }

    public function images(){
        return $this->belongsToMany('App\Image', 'product_has_image')->withTimestamps();
    }

    public function category(){
        return $this->belongsTo('App\Category');
    }

    public function manufacturer(){
        return $this->belongsTo('App\Manufacturer');
    }

    public function prices(){
        return $this->belongsToMany('App\Price', 'product_has_price')->withPivot('value', 'currency_id')->withTimestamps();
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

    public function getActiveProducts(){
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
            ->where('products.active', 1)
            ->orderBy('products.name')
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
            'products.height'
        )
            ->where('id', '=', $id)

            ->where('active', '=', 1)

            /************PRICE******************/
            ->with(['prices' => function ($query) {
                $query->where('name', '=', 'retail')
                ->where('product_has_price.active', '=', '1');
            }])

            /************BRAND******************/
            ->with('brands')

            /************IMAGE******************/
            ->with('images')

            /************MANUFACTURER***********/
            ->with('manufacturer')

            /************CATEGORY***************/
            ->with('category')

            ->get();

            if( isset($products[0])){
                $products = $this->setMainCurrencyPriceValue($products);
                return $products[0];
            }else{
                return null;
            }

    }

    public function getActiveProductsFromCategory($category_id){

        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail'
        )
            ->where('products.category_id', $category_id)

            ->where('products.active', 1)

            /************PRICE******************/
            ->with(['prices' => function ($query) {
                $query->where('name', '=', 'retail')
                    ->where('product_has_price.active', '=', '1');
            }])

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->with('manufacturer')

            ->orderBy('products.name')

            ->get();


        return $this->setMainCurrencyPriceValue($products);

    }

    public function getFilteredProducts($parameters){

        $products = self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail'
        )
            ->where('products.active', 1)

            /************PRICE*******************/
            /*
            ->when(isset($parameters['price']), function ($query) use ($parameters) {
                return $query->whereHas('prices', function($query) use ($parameters) {
                    $query->where('name', '=', 'retail')
                        ->where('product_has_price.active', '=', '1')
                        ->whereBetween('value', (explode('|', $parameters['price'])) );
                });
            })
            */
            ->with(['prices' => function ($query) {
                $query->where('name', '=', 'retail')
                    ->where('product_has_price.active', '=', '1');
            }])

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
            ->with('manufacturer')

            /************CATEGORY***************/
            ->when(isset($parameters['category']), function ($query) use ($parameters) {
                return $query->whereIn('products.category_id', explode('|', $parameters['category']));
            })

            ->orderBy('products.id')

            ->get();


        $products = $this->setMainCurrencyPriceValue($products);

        $filtered = $products->filter(function ($value, $key) use ($parameters){

            $price = $value->prices[0]->value;

            $min_max_array = (explode('|', $parameters['price']));

            return $price > $min_max_array[0] & $price < $min_max_array[1];
        });

        //todo переделать выборку
        // Значения из фильтра приходят в рублях,
        // а значения для товарах хранятся в разной валюте

        return $filtered;

    }

    public function getActiveProductsOfBrand($brand_id){

        $products = self::select(
            'products.id',
            'products.manufacturer_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail'
        )
            ->where('products.active', 1)

            /************PRICE******************/
            ->with(['prices' => function ($query) {
                $query->where('name', '=', 'retail')
                    ->where('product_has_price.active', '=', '1');
            }])

            /************BRAND******************/
            ->with(['brands' => function ($query) use ($brand_id){
                $query->where('active', '=', 1)
                    ->where('brand_id', '=', $brand_id);
            }])

            /************MANUFACTURER***********/
            ->with('manufacturer')

            /************CATEGORY***************/
            ->with('category')

            ->orderBy('products.name')

            ->get();

        return $this->setMainCurrencyPriceValue($products);
    }

    public function getProductsById($idProducts){

        $products = self::select(
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
            'products.height'
        )
            ->whereIn('id', $idProducts)

            ->where('active', '=', 1)

            /************PRICE******************/
            ->with(['prices' => function ($query) {
                $query->where('name', '=', 'retail')
                    ->where('product_has_price.active', '=', '1');
            }])

            /************BRAND******************/
            ->with('brands')

            /************MANUFACTURER***********/
            ->with('manufacturer')

            /************CATEGORY***************/
            ->with('category')

            ->orderBy('products.name')

            ->get();

        return $this->setMainCurrencyPriceValue($products);

    }

    public function getProductsFromJson($json_string){

        $json_products = json_decode($json_string, true);

        $id_products = [];

        foreach($json_products as $json_product){
            $id_products[] = $json_product['id'];
        }

        $products = $this->getProductsById($id_products);
        $products = $products->keyBy('id');

        foreach($json_products as $json_product){
            $products[$json_product['id']]->quantity = $json_product['quantity'];
        }

        return $products;
    }

    public function checkActiveProductsInCategory($category_id){
        return self::where('active', 1)
            ->where('category_id', $category_id)
            ->count();
    }

    public function getTotal($products){

        $total = 0;

        foreach($products as $product){

            $total += $product->quantity * $product->prices[0]->value;

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

    private function setMainCurrencyPriceValue($products){

        $currencies = $this->currencies->getAllCurrencies();

        foreach($products as $key => $product){

            if( isset( $product->prices[0] ) ){

                $currencyId = $product->prices[0]->pivot->currency_id;

                $currency = $currencies->first(function ($value, $key) use ($currencyId){
                    return $value->id === $currencyId;
                });

                $price = $currency->value * $product->prices[0]->pivot->value;

                $product->prices[0]->value = round($price, 0);
            }

        }

        return $products;

    }


}
