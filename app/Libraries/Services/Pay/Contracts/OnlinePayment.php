<?php

namespace App\Libraries\Services\Pay\Contracts;

use App\Models\Shop\Customer;
use App\Models\Shop\Order\Basket;
use App\Models\Shop\Order\Order;
use App\Models\Shop\Product\Product;
use Illuminate\Http\Request;

interface OnlinePayment{

    public function send(Request $request, Basket $basket);

    public function confirm(Request $request, Basket $baskets, Product $products);

    public function execute(Request $request, Order $orders, Basket $baskets, Customer $customers, Product $products);

    public function redirect(Request $request, Order $orders, $msg);
}