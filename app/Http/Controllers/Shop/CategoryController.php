<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\ShopBasket;
use Illuminate\Http\Request;
use App\Category;
use App\Product;

class CategoryController extends Controller{

    protected $categories;
    protected $baskets;
    protected $data;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @param  Category $categories
     * @return void
     */
    public function __construct(Category $categories, ShopBasket $baskets){
        $this->categories   = $categories;
        $this->baskets      = $baskets;
        $this->data         = [
            'template'  => [
                'component'     => 'shop',
                'resource'      => 'category',
                ],
            'data'      => [
                'product_chunk' => 3
            ]
        ];

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){

        $this->data['template'] ['view']        = 'list';
        $this->data['data']     ['categories']  =  $this->categories->getCategoriesTree();
        $this->data['data']     ['header_page'] =  'Категории';

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
     * @param  Product $products
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Product $products, $id){

        if( $request->ajax() ){

            $this->data['data'] ['products'] = $products->getFilteredProductsFromCategory($id, $request->toArray());

            return response()->view( 'components.shop.product.list', $this->data['data'])->header('Cache-Control', 'no-store');
        }

        $category = $this->categories->getCategory($id);

        $this->data['template'] ['view']                = 'show';
        $this->data['template'] ['sidebar']             = 'product_filter';
        $this->data['template'] ['filter-tags']         = 'filter-tags';
        $this->data['data']     ['category']            = $category;
        $this->data['data']     ['children_categories'] = $this->categories->getActiveChildrenCategories($id);
        $this->data['data']     ['header_page']         = $category[0]->name;

        $this->data['template']['custom'][] = 'shop-icons';

        if( count( $request->query ) > 0 ){

            $this->data['data'] ['products'] = $products->getFilteredProductsFromCategory($id, $request->toArray());

        }else{

            $this->data['data'] ['products'] = $products->getActiveProductsFromCategory($id);

        }

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
    public function update(Request $request, $id){
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
