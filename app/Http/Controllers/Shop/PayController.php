<?php

namespace App\Http\Controllers\Shop;

use App\Libraries\Services\Pay\Contracts\OnlinePayment;
use App\Models\Shop\Order\Basket;
use App\Models\Shop\Order\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Product\Product;
use App\Models\Shop\Customer;

class PayController extends Controller{

    private $payment;

    public function __construct(OnlinePayment $payment){

        $this->payment = $payment;

    }

    public function confirm(Request $request, Basket $baskets, Product $products){

        return $this->payment->confirm($request, $baskets, $products);

    }

    public function execute(Request $request, Order $orders, Basket $baskets, Customer $customers, Product $products){

        return $this->payment->execute($request, $orders, $baskets, $customers, $products);

    }

    public function redirect(Request $request, Order $orders, $msg){

        return $this->payment->redirect($request, $orders, $msg);

    }

}
