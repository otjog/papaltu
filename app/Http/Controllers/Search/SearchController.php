<?php

namespace App\Http\Controllers\Search;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Product\Product;
use App\Models\Shop\Order\Basket;
use sngrl\SphinxSearch\SphinxSearch;

class SearchController extends Controller{

    protected $data;

    protected $products;

    protected $query;

    protected $baskets;

    protected $template_name;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @return void
     */
    public function __construct(Request $request, Product $products, Basket $baskets){

        $this->template_name = env('SITE_TEMPLATE');

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
            ],
            'template_name' => $this->template_name
        ];

    }

    public function show(){

        $sphinx  = new SphinxSearch();

        $searchIdResult = $sphinx->search($this->query, env( 'SPHINXSEARCH_INDEX' ))->query();

        $this->data['template'] ['view']        = 'show';

        $this->data['data']     ['products']    = [];
        $this->data['data']     ['query']       = $this->query;

        if( isset( $searchIdResult[ 'matches' ] ) && count( $searchIdResult[ 'matches' ] ) > 0 ){
            $this->data['data'] ['products'] = $this->products->getProductsById( array_keys( $searchIdResult[ 'matches' ] ) );
        }

        return view( 'templates.default', $this->data);
    }

}
