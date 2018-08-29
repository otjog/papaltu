<?php

namespace App\Http\Controllers\Shop;

use App\Models\DeliveryServices;
use App\Product;
use App\ShopBasket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BasketController extends Controller{

    protected $baskets;

    protected $data;

    public function __construct(ShopBasket $baskets){
        $this->baskets = $baskets;
        $this->data = [
            'template'  =>  [
                'component' => 'shop',
                'resource'  => 'basket'
            ]
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function store(Request $request){

        $token = $request->session()->get('_token');

        $basket = $this->baskets->getActiveBasket( $token );

        if($basket === null){
            $this->baskets->token     = $token;

            $this->baskets->products  = json_encode(array($request->all()));

            $this->baskets->save();
        }else{
            $products = $this->addProductToArray( $basket, $request->all() );

            $basket->products = json_encode($products);

            $basket->save();
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function show($token){


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $products, DeliveryServices $ds, $token){

        $basket = $this->baskets->getActiveBasket( $token );

        if($basket->order_id === null){

            $productsFromBasket = $products->getProductsFromBasket($basket);

            $this->data['template']['view']         = 'edit';

            $this->data['data']['basketProducts']   = $productsFromBasket;

            return view( 'templates.default', $this->data);

        } else {

            return redirect('orders.show', $basket->id . '-' . $basket->order_id);

        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $token){

        $basket = $this->baskets->getActiveBasket( $token ) ;

        $products = $this->changeQuantityInArray( $request->all() );

        $basket->products = json_encode($products);

        $basket->save();

        return back();
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

    private function changeQuantityInArray($products){

        $newProducts = [];

        foreach ($products as $key => $product){

            if( substr( $key, 0, 1 ) !== '_'){

                $product['quantity'] = (int)$product['quantity'];

                if($product['quantity'] > 0){

                    $newProducts[] = $product;

                }

            }

        }

        return $newProducts;
    }

    private function addProductToArray($basket, $newProduct){
        $products = json_decode($basket->products, true);

        foreach($products as $key => $product){

            if(isset($newProduct) && $product['id'] === $newProduct['id']){
                $quantity = $product['quantity']*1 + $newProduct['quantity']*1;
                $products[$key]['quantity'] = (string) $quantity;
                unset($newProduct);
            }

        }

        if(isset($newProduct)){
            $products[] = $newProduct;
        }

        return $products;
    }
}
