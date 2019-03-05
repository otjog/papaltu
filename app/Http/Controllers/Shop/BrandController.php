<?php

namespace App\Http\Controllers\Shop;

use App\Models\Shop\Product\Brand;
use App\Models\Seo\MetaTagsCreater;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Product\Product;
use App\Models\Shop\Order\Basket;
use App\Models\Settings;

class BrandController extends Controller{

    protected $brands;

    protected $baskets;

    protected $settings;

    protected $data = [];

    protected $metaTagsCreater;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @return void
     */
    public function __construct(Brand $brands, Basket $baskets, MetaTagsCreater $metaTagsCreater)
    {

        $this->settings = Settings::getInstance();

        $this->brands = $brands;

        $this->baskets = $baskets;

        $this->metaTagsCreater = $metaTagsCreater;

        $this->data['template'] = [
            'component'     => 'shop',
            'resource'      => 'brand',
        ];

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){

        $this->data['global_data']['project_data'] = $this->settings->getParameters();

        $this->data['template'] ['view']    = 'list';
        $this->data['data']     ['brands']  = $this->brands->getActiveBrands();
        $this->data['data']     ['header_page'] =  'Бренды';

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
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Product $products, $name){

        $brand = $this->brands->getBrand($name);

        $this->data['global_data']['project_data'] = $this->settings->getParameters();

        $this->data['template'] ['view']        = 'show';
        $this->data['template'] ['sidebar']     = 'product_filter';
        $this->data['template'] ['filter-tags'] = 'filter-tags';
        $this->data['data']     ['brand']       = $brand;
        $this->data['data']     ['header_page'] = 'Товары бренда ' . $brand[0]->name;
        $this->data['data']     ['parameters']  = [];

        $this->data['template']['custom'][] = 'shop-icons';

        if (count($request->query) > 0) {

            $routeData = ['brand' => $name];

            $filterData = $request->toArray();

            $this->data['data']['products'] = $products->getFilteredProducts($routeData, $filterData);
        } else {
            $this->data['data']['products'] = $products->getActiveProductsOfBrand($name);
        }

        $this->data['meta'] = $this->metaTagsCreater->getMetaTags($this->data);
//dd($this->data);
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
