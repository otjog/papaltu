<?php

namespace App\Http\Controllers\Shop;

use App\Models\DeliveryServices;
use App\Payment;
use App\Shipment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\ShopBasket;
use App\ShopOrder;
use App\Product;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderShipped;

class OrderController extends Controller{


    protected $orders;
    protected $baskets;
    protected $data;

    public function __construct(ShopOrder $orders, ShopBasket $baskets){
        $this->orders   = $orders;
        $this->baskets  = $baskets;
        $this->data = [
            'template'  =>  [
                'component' => 'shop',
                'resource'  => 'order',
            ],
            'data'      => [
                'chunk' => 3
            ]
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        //GET
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Product $products){

        $data = $request->all();

        $basket = $this->baskets->getActiveBasket( $data['_token'] );

        $data = $this->prepareData($data, $basket);

        $order = $this->orders->create($data);

        $basket->order_id = $order->id;
        $basket->save();

        Mail::to(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
            ->send(new OrderShipped(
                [
                    'data'      => $data,
                    'products'  =>$products->getProductsFromJson($basket->products)
                ])
            );

        return redirect('orders/'.$order->id)->with('status', 'Заказ оформлен! Скоро с вами свяжется наш менеджер');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Product $products, Payment $payments, Shipment $shipments, DeliveryServices $ds){
        $token = $request->session()->get('_token');

        $basket = $this->baskets->getActiveBasket( $token );

        $productsFromBasket = $products->getProductsFromBasket($basket);

        $parcel = $products->getParcelParameters($productsFromBasket);

        $this->data['template']['view']         = 'create';

        $this->data['data']['basketProducts']   = $productsFromBasket;

        $this->data['data']['payments']         = $payments->getActiveMethods();

        $this->data['data']['shipments']        = $shipments->getActiveMethods();

        $this->data['data']['delivery']  = $ds->getDeliveryDataForProduct($request->session(), $parcel);

        return view( 'templates.default', $this->data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id){

        $this->data['template']['view'] = 'show';
        $this->data['data']['order']    = $this->orders->getOrderByBasketIdAndOrderId($id);

        return view( 'templates.default', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id){
        //GET
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        //PUT/PATCH
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        //DELETE
    }

    /*****************Helpers**********************/

    private function prepareData($data, $basket){
        unset($data['_token']);

        $data['ordered']        = 1;
        $data['shop_basket_id'] = $basket->id;
        $data['products']       = $basket->products;


        $names = explode(' ', $data['full_name']);

        for($i = 0; $i < count($names); $i++){
            switch($i){
                case 0 : $data['last_name']     = $names[$i];break;
                case 1 : $data['first_name']    = $names[$i];break;
                case 2 : $data['middle_name']   = $names[$i];break;
            }
        }

        return $data;
    }
}
