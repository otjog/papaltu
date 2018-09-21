<?php

namespace App\Http\Controllers\Shop;

use App\Libraries\Services\Pay\Contracts\OnlinePayment;
use App\Models\Shop\Customer;
use App\Models\Shop\Order\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Order\Basket;
use App\Models\Shop\Order\Order;
use App\Models\Shop\Product\Product;

class OrderController extends Controller{

    protected $orders;

    protected $baskets;

    protected $data;

    protected $template_name;

    public function __construct(Order $orders, Basket $baskets){

        $this->template_name = env('SITE_TEMPLATE');

        $this->orders   = $orders;

        $this->baskets  = $baskets;

        $this->data = [
            'template'  =>  [
                'component' => 'shop',
                'resource'  => 'order',
            ],
            'data'      => [
                'chunk' => 3
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
        //GET
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Payment $payments, OnlinePayment $paymentService, Customer $customers, Product $products){

        $token = $request['_token'];

        $payment = $payments->getMethodById($request->payment_id);

        if($payment[0]->alias === 'online'){

            $basket = $this->baskets->getActiveBasketWithProducts($products, $token );

            return $paymentService->send($request, $basket);

        }else{

            $basket = $this->baskets->getActiveBasket( $token );

            $customer = $customers->findOrCreateCustomer( $request->all() );

            $order = $this->orders->storeOrder( $request->all(), $basket, $customer, $products );

            return redirect('orders/'.$order->id)->with('status', 'Заказ оформлен! Скоро с вами свяжется наш менеджер');
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Product $products, Payment $payments){

        $token = $request->session()->get('_token');

        $this->data['template']['view'] = 'create';

        $this->data['data']['basket']   = $this->baskets->getActiveBasketWithProducts( $products, $token );

        $this->data['data']['payments'] = $payments->getActiveMethods();

        return view( 'templates.default', $this->data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $products, $id){

        $this->data['template']['view'] = 'show';

        $this->data['data']['order']    = $this->orders->getOrderById($products, $id);

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
}