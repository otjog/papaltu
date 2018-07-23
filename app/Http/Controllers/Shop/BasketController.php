<?php

namespace App\Http\Controllers\Shop;

use App\Product;
use App\ShopBasket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BasketController extends Controller
{

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

    public function getIndex(){

    }

    public function getShow(Request $request, Product $products){

        $token = $request->session()->get('_token');

        $basket = $this->baskets->getActiveBasket( $token );

        $this->data['template']['view']         = 'show';
        $this->data['data']['basketProducts']   = $products->getProductsFromBasket($basket);

        return view( 'templates.default', $this->data);
    }

    public function postAdd(Request $request){

        $token = $request->session()->get('_token');

        $basket = $this->baskets->getActiveBasket( $token );

        if($basket === null){
            $this->store( $token, $request->all() );
        }else{
            $products = $this->addProductToArray( $basket, $request->all() );
            $this->update( $basket, $products );
        }

    }

    public function getChange(Request $request){

        $token = $request->session()->get('_token');

        $basket = $this->baskets->getActiveBasket( $token) ;

        $products = $this->changeQuantityInArray( $request->all() );

        $this->update( $basket, $products );

        return redirect($request->server('HTTP_REFERER'));

    }

    public function deleteDestroy($id){

        //DELETE
    }


    private function store($token, $newProduct){

        $this->baskets->token     = $token;
        $this->baskets->products  = json_encode(array($newProduct));
        $this->baskets->save();

    }

    private function update($basket, $products){

        $basket->products = json_encode($products);
        $basket->save();

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

    private function changeQuantityInArray($products){

        $newProducts = [];

        foreach ($products as $product){
            $product['quantity'] = (int)$product['quantity'];
            if($product['quantity'] > 0){
                $newProducts[] = $product;
            }
        }

        return $newProducts;
    }

}
