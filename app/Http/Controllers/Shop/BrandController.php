<?php

namespace App\Http\Controllers\Shop;

use App\Brand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Product;
use App\ShopBasket;

class BrandController extends Controller{

    protected $brands;
    protected $baskets;
    protected $data;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @return void
     */
    public function __construct(Brand $brands, ShopBasket $baskets){
        $this->brands   = $brands;
        $this->baskets  = $baskets;
        $this->data     = [
            'template'  => [
                'component'     => 'shop',
                'resource'      => 'brand',
            ],
            'data'      => [
                'product_chunk' => 4
            ]
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

        if($request->ajax()){
            return response()->view( 'components.shop.category.show', $this->data['data'])->header('Cache-Control', 'no-store');
        }

        $this->data['template'] ['view']        = 'show';
        $this->data['data']     ['brand']       = $this->brands->getActiveBrand($id);

        if(count($request->query) > 0){
            $this->data['data']['products'] = $products->getFilteredProductsFromCategory($id, $request->toArray());

        }else{
            $this->data['data']['products'] = $products->getActiveProductsOfBrand($id);
        }

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

    public function productToBasket(Request $request, Product $products, $id){
        $basket = new BasketController($this->baskets);
        $basket->postAdd($request);

        //return $this->show($request, $products, $id);
        return redirect()->route('products.show', ['id' => $id]);

    }
}
