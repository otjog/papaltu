<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Order\Basket;
use Illuminate\Http\Request;
use App\Models\Shop\Product\Product;
use App\Models\Seo\MetaTagsCreater;
use App\Models\Settings;
class ProductController extends Controller{

    protected $products;

    protected $baskets;

    protected $data;

    protected $metaTagsCreater;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @param  Product $products
     * @return void
     */
    public function __construct(Product $products, Basket $baskets, MetaTagsCreater $metaTagsCreater ){

        $settings = Settings::getInstance();

        $this->data = $settings->getParameters();

        $this->products = $products;

        $this->baskets = $baskets;

        $this->metaTagsCreater = $metaTagsCreater;

        $this->data['template'] = [
            'component' => 'shop',
            'resource'  => 'product'
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id){

        $this->data['template']['view'] = 'show';

        $this->data['template']['custom'][] = 'shop-icons';

        $this->data['data']['product']  = $this->products->getActiveProduct($id);

        $this->data['meta'] = $this->metaTagsCreater->getMetaTags($this->data);

        return view( 'templates.default', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id){
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        //
    }

}