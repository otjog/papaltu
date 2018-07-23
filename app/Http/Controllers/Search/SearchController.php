<?php

namespace App\Http\Controllers\Search;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Product;
use App\Http\Controllers\Shop\BasketController;
use App\ShopBasket;
use sngrl\SphinxSearch\SphinxSearch;

class SearchController extends Controller{

    protected $data;
    protected $products;
    protected $query;
    protected $baskets;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @return void
     */
    public function __construct(Request $request, Product $products, ShopBasket $baskets){

        $this->products = $products;
        $this->baskets  = $baskets;
        $this->query    = $request->search;
        $this->data     = [
            'template'  => [
                'component'     => 'shop',
                'resource'      => 'search',
            ],
            'data'      => [
                'product_chunk' => 4
            ]
        ];

    }

    public function show(){

        $sphinx  = new SphinxSearch();

        $searchIdResult = $sphinx->search($this->query, 'celtic_products_search')->query();

        $this->data['template'] ['view']        = 'show';

        $this->data['data']     ['products']    = [];
        $this->data['data']     ['query']       = $this->query;

        if( isset( $searchIdResult[ 'matches' ] ) && count( $searchIdResult[ 'matches' ] ) > 0 ){
            $this->data['data'] ['products'] = $this->products->getProductsById( array_keys( $searchIdResult[ 'matches' ] ) );
        }

        return view( 'templates.default', $this->data);
    }

    public function productToBasket(Request $request){
        $basket = new BasketController($this->baskets);
        $basket->postAdd($request);
        //todo исправить баг с post Запросом и обновлением страницы
        return $this->show();


    }

}
