<?php

namespace App\Http\Controllers\Shop;

use App\Libraries\Services\Pay\Contracts\OnlinePayment;
use App\ShopBasket;
use App\ShopOrder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Product;
use App\Models\Shop\Customer;

class PayController extends Controller{

    private $payment;

    public function __construct(OnlinePayment $payment){

        $this->payment = $payment;

    }

    public function confirm(Request $request, ShopBasket $baskets, Product $products){

        return $this->payment->confirm($request, $baskets, $products);

    }

    public function execute(Request $request, ShopOrder $orders, ShopBasket $baskets, Customer $customers){

        return $this->payment->execute($request, $orders, $baskets, $customers);

    }

    public function redirect(Request $request, ShopOrder $orders, $msg){

        return $this->payment->redirect($request, $orders, $msg);

    }

}
