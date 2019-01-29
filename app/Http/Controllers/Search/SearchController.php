<?php

namespace App\Http\Controllers\Search;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Product\Product;
use App\Models\Shop\Order\Basket;
use sngrl\SphinxSearch\SphinxSearch;
use App\Models\Settings;

class SearchController extends Controller{

    protected $data = [];

    protected $settings;

    protected $products;

    protected $queryString;

    protected $baskets;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @return void
     */
    public function __construct(Request $request, Product $products, Basket $baskets){

        $this->settings = Settings::getInstance();

        $this->products = $products;

        $this->baskets  = $baskets;

        $this->queryString    = $request->search;

        $this->data['template']     = [
            'component'     => 'shop',
            'resource'      => 'search',
        ];
        $this->data['data']['parameters']  = $request->toArray();


    }

    public function show(){

        $sphinx  = new SphinxSearch();

        $searchIdResult = $sphinx->search($this->queryString, env( 'SPHINXSEARCH_INDEX' ))->query();

        $this->data['global_data']['project_data'] = $this->settings->getParameters();

        $this->data['template'] ['view']        = 'show';

        $this->data['data']     ['products']    = [];
        $this->data['data']     ['query']       = $this->queryString;
        $this->data['data']     ['header_page'] = 'Результаты поиска по запросу: ' . $this->queryString;

        if( isset( $searchIdResult[ 'matches' ] ) && count( $searchIdResult[ 'matches' ] ) > 0 ){
            $this->data['data'] ['products'] = $this->products->getProductsById( array_keys( $searchIdResult[ 'matches' ] ) );
        }



        return view( 'templates.default', $this->data);
    }

}
