<?php

namespace App;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use sngrl\SphinxSearch\SphinxSearch;

class Product extends Model{

    protected $fillable = ['brand_id', 'category_id', 'manufacturer_id', 'active', 'name', 'scu'];

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
        return $this->belongsToMany('App\Price', 'product_has_price')->withPivot('value')->withTimestamps();
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

        return self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.description',
            'products.thumbnail'
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

            ->first();

    }

    public function getActiveProductsFromCategory($category_id){

        return self::select(
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

    }

    public function getFilteredProductsFromCategory($category_id, $parameters){

        return self::select(
            'products.id',
            'products.manufacturer_id',
            'products.category_id',
            'products.name',
            'products.original_name',
            'products.scu',
            'products.thumbnail'
        )
            ->where('products.category_id', $category_id)

            ->where('products.active', 1)

            /************PRICE*******************/
            ->when(isset($parameters['price']), function ($query) use ($parameters) {
                return $query->whereHas('prices', function($query) use ($parameters) {
                    $query->where('name', '=', 'retail')
                        ->where('product_has_price.active', '=', '1')
                        ->whereBetween('value', explode('|', $parameters['price']));
                });
            })
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

            ->orderBy('products.id')

            ->get();

    }

    public function getActiveProductsOfBrand($brand_id){

        return self::select(
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
    }

    public function getProductsById($idProducts){

        return self::select(
            'id',
            'manufacturer_id',
            'category_id',
            'name',
            'original_name',
            'scu',
            'thumbnail'
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

}
