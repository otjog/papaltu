<?php

namespace App;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use sngrl\SphinxSearch\SphinxSearch;

class Product extends Model{

    protected $fillable = ['brand_id', 'category_id', 'active', 'name', 'scu'];

    public function brands(){
        return $this->belongsToMany('App\Brand', 'product_has_brand')->withTimestamps();
    }

    public function category(){
        return $this->belongsTo('App\Category');
    }

    public function prices(){
        return $this->belongsToMany('App\Price', 'product_has_price')->withPivot('value')->withTimestamps();
    }

    public function getAllProducts(){
        return self::select(
            'products.id',
            'products.category_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.image',
            'products.thumbnail',
            'products.url'
        )
            ->orderBy('name')
            ->get();
    }

    public function getActiveProducts(){
        return self::select(
            'products.id',
            'products.brand_id',
            'products.category_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.image',
            'products.thumbnail',
            'products.url'
        )
            ->where('products.active', 1)
            ->orderBy('products.name')
            ->get();
    }

    public function getActiveProduct($id){
        $products = self::select(
            'products.id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.image',
            'brands.name                as brand_name',
            'manufacturers.name         as manufacturer_name',
            'prices.currency            as currency_char_code',
            'product_has_price.value    as price'
        )

            /************PRICE******************/
            ->leftJoin('product_has_price', function ($join) {
                $join->on('product_has_price.product_id', '=', 'products.id')
                    ->where('product_has_price.active', 1);
            })
            ->leftJoin('prices', 'product_has_price.price_id', '=', 'prices.id')

            /************BRAND******************/
            ->leftJoin('product_has_brand', function ($join) {
                $join->on('product_has_brand.product_id', '=', 'products.id');
            })
            ->leftJoin('brands', 'product_has_brand.brand_id', '=', 'brands.id')

            /************MANUFACTURER******************/
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'products.manufacturer_id')

            /************CURRENCY******************/
            ->leftJoin('currency', 'prices.currency', '=', 'currency.char_code')

            ->where('prices.name', 'retail')
            ->where('products.active', 1)
            ->where('products.id', $id)
            ->get();

        $products = $this->addBrandsArrayToProducts($products);

        $products = $this->createProductName($products);

        return $products[0];


    }

    public function getActiveProductsFromCategory($category_id){

        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.image',
            'products.thumbnail',
            'products.url',
            'manufacturers.name         as manufacturer_name',
            'brands.name                as brand_name',
            'prices.name                as price_name',
            'prices.currency            as currency_char_code',
            'product_has_price.value    as price'
        )
            ->leftJoin('product_has_price', function ($join) {
                $join->on('product_has_price.product_id', '=', 'products.id')
                    ->where('product_has_price.active', 1);
            })
            ->leftJoin('prices', 'product_has_price.price_id', '=', 'prices.id')

            ->leftJoin('product_has_brand', function ($join) {
                $join->on('product_has_brand.product_id', '=', 'products.id');
            })
            ->leftJoin('brands', 'product_has_brand.brand_id', '=', 'brands.id')

            ->leftJoin('manufacturers', 'products.manufacturer_id', '=', 'manufacturers.id')

            ->leftJoin('currency', 'prices.currency', '=', 'currency.char_code')
            ->where('prices.name', 'retail')
            ->where('products.category_id', $category_id)
            ->where('products.active', 1)
            ->orderBy('products.name')
            ->get();

        $products = $this->addBrandsArrayToProducts($products);

        $products = $this->createProductName($products);

        return $products;
    }

    public function getFilteredProductsFromCategory($category_id, $parameters){

        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.image',
            'products.thumbnail',
            'products.url',
            'manufacturers.name         as manufacturer_name',
            'brands.name                as brand_name',
            'prices.name                as price_name',
            'prices.currency            as currency_char_code',
            'product_has_price.value    as price'
        )
            /***********price************/
            ->leftJoin('product_has_price', function ($join) use($parameters) {
                $join->on('product_has_price.product_id', '=', 'products.id')
                    ->where('product_has_price.active', 1)
                    ->when(isset($parameters['price']), function ($query) use ($parameters) {
                        return $query->whereBetween('value', explode('|', $parameters['price']));
                    });
            })
            ->leftJoin('prices', 'product_has_price.price_id', '=', 'prices.id')

            /***********brand***********/
            ->when(isset($parameters['brand']), function ($query) use ($parameters) {
                return $query->whereIn('product_has_brand.brand_id', explode('|', $parameters['brand']));
            })
            ->leftJoin('product_has_brand', function ($join) {
                $join->on('product_has_brand.product_id', '=', 'products.id');
            })
            ->leftJoin('brands', 'product_has_brand.brand_id', '=', 'brands.id')

            /***********manufacturer***********/
            ->leftJoin('manufacturers', 'products.manufacturer_id', '=', 'manufacturers.id')
            ->when(isset($parameters['manufacturer']), function ($query) use ($parameters) {
                return $query->whereIn('products.manufacturer_id', explode('|', $parameters['manufacturer']));
            })

            ->leftJoin('currency', 'prices.currency', '=', 'currency.char_code')
            ->where('prices.name', 'retail')
            ->where('products.category_id', $category_id)
            ->where('products.active', 1)
            ->orderBy('products.name')
            ->get();

        $products = $this->addBrandsArrayToProducts($products);

        $products = $this->createProductName($products);

        return $products;
    }

    public function getProductsFromCategory($category_id){
        return self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.image',
            'products.thumbnail',
            'products.url',
            'manufacturers.name         as manufacturer_name',
            'prices.name                as price_name',
            'prices.currency            as currency_char_code',
            'currency.value             as currency_quotation',
            'product_has_price.value    as currency_price'
        )
            ->leftJoin('product_has_price', function ($join) {
                $join->on('product_has_price.product_id', '=', 'products.id')
                    ->where('product_has_price.active', 1);
            })
            ->leftJoin('prices', 'product_has_price.price_id', '=', 'prices.id')

            ->leftJoin('manufacturers', 'products.manufacturer_id', '=', 'manufacturers.id')

            ->leftJoin('currency', 'prices.currency', '=', 'currency.char_code')
            ->where('prices.name', 'retail')
            ->where('products.category_id', $category_id)
            ->orderBy('products.name')
            ->get();
    }

    public function getActiveProductsOfBrand($brand_id){
        $products =  self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.active',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.image',
            'products.thumbnail',
            'products.url',
            'manufacturers.name         as manufacturer_name',
            'brands.name                as brand_name',
            'prices.name                as price_name',
            'prices.currency            as currency_char_code',
            'product_has_price.value    as price'
        )
            ->leftJoin('product_has_price', function ($join) {
                $join->on('product_has_price.product_id', '=', 'products.id')
                    ->where('product_has_price.active', 1);
            })
            ->leftJoin('prices', 'product_has_price.price_id', '=', 'prices.id')

            ->leftJoin('product_has_brand', function ($join) {
                $join->on('product_has_brand.product_id', '=', 'products.id');
            })
            ->leftJoin('brands', 'product_has_brand.brand_id', '=', 'brands.id')

            ->leftJoin('manufacturers', 'products.manufacturer_id', '=', 'manufacturers.id')

            ->leftJoin('currency', 'prices.currency', '=', 'currency.char_code')
            ->where('prices.name', 'retail')
            ->where('brands.id', $brand_id)
            ->where('products.active', 1)
            ->orderBy('products.name')
            ->get();

        $products = $this->addBrandsArrayToProducts($products);

        $products = $this->createProductName($products);

        return $products;

    }

    public function getProductsById($idProducts){
        $products = self::select(
            'products.id',
            'products.manufacturer_id',
            'products.original_name',
            'products.name',
            'products.scu',
            'products.thumbnail',
            'manufacturers.name         as manufacturer_name',
            'brands.name                as brand_name',
            'prices.name                as price_name',
            'prices.currency            as currency_char_code',
            'product_has_price.value    as price'
        )
            ->leftJoin('product_has_price', function ($join) {
                $join->on('product_has_price.product_id', '=', 'products.id')
                    ->where('product_has_price.active', 1);
            })
            ->leftJoin('prices', 'product_has_price.price_id', '=', 'prices.id')

            ->leftJoin('product_has_brand', function ($join) {
                $join->on('product_has_brand.product_id', '=', 'products.id');
            })
            ->leftJoin('brands', 'product_has_brand.brand_id', '=', 'brands.id')

            ->leftJoin('manufacturers', 'products.manufacturer_id', '=', 'manufacturers.id')

            ->leftJoin('currency', 'prices.currency', '=', 'currency.char_code')
            ->where('prices.name', 'retail')
            ->whereIn('products.id', $idProducts)
            ->get();

        $products = $this->addBrandsArrayToProducts($products);

        $products = $this->createProductName($products);

        return $products;

    }

    public function getProductsFromBasket($basket){

        if($basket === null){
            return [];
        }

        return $this->getProductsFromJson($basket['products']);

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

    /**************Helpers************************************/

    protected function addBrandsArrayToProducts($products){
        $temporary = [];
        foreach($products as $key => $product){

            if( isset( $temporary[ $product->id ] ) ){
                $brands = $products[ $temporary[ $product->id ] ][ 'brand_name' ];
                $brands[] = $product->brand_name;

                $products[ $temporary[ $product->id ] ][ 'brand_name' ] = $brands;
                $products->forget($key);
            }else{
                $temporary[ $product->id ] = $key;
                $product->brands = [ $product->brand_name ];
                unset($products[$key]->brand_name);
            }

        }
        return $products->values();
    }

    protected function createProductName($products){

        foreach($products as $key => $product){
            if($product->name === null || $product->name === ''){
                $products[$key]->name = $product->original_name;
            }else{
                if( $product->manufacturer_name !== null ){
                    $products[$key]->name = $product->manufacturer_name . ' ' . $product->name;
                }

                if( $product->brands[0] !== null ){
                    foreach($product->brands as $brand_name){
                        $products[$key]->name .= ' - ' . $brand_name;
                    }
                }
            }
        }

        return $products;

    }

}
