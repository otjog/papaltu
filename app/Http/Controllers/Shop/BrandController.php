<?php

namespace App\Http\Controllers\Shop;

use App\Models\Shop\Product\Brand;
use App\Models\Seo\MetaTagsCreater;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Product\Product;
use App\Models\Shop\Order\Basket;

class BrandController extends Controller{

    protected $brands;

    protected $baskets;

    protected $data;

    protected $metaTagsCreater;

    protected $template_name;
    /**
     * Создание нового экземпляра контроллера.
     *
     * @return void
     */
    public function __construct(Brand $brands, Basket $baskets, MetaTagsCreater $metaTagsCreater){

        $this->template_name = env('SITE_TEMPLATE');

        $this->brands   = $brands;

        $this->baskets  = $baskets;

        $this->metaTagsCreater = $metaTagsCreater;

        $this->data     = [
            'template'  => [
                'component'     => 'shop',
                'resource'      => 'brand',
            ],
            'data'      => [
                'product_chunk' => 4
            ],
            'template_name' => $this->template_name
        ];

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $this->data['template'] ['view']    = 'list';
        $this->data['data']     ['brands']  = $this->brands->getActiveBrands();

        return view( 'templates.default', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Product $products, $id){

        $this->data['template'] ['view']        = 'show';

        $this->data['data']     ['brand']       = $this->brands->getActiveBrand($id);

        if(count($request->query) > 0){
            $this->data['data']['products'] = $products->getFilteredProducts($request->toArray());

        }else{
            $this->data['data']['products'] = $products->getActiveProductsOfBrand($id);
        }

        $this->data['meta'] = $this->metaTagsCreater->getMetaTags($this->data);

        return view( 'templates.default', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
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
    public function destroy($id)
    {
        //
    }

}
