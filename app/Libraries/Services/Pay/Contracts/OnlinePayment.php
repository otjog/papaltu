<?php

namespace App\Libraries\Services\Pay\Contracts;

use App\Models\Shop\Customer;
use App\ShopBasket;
use App\ShopOrder;
use App\Product;
use Illuminate\Http\Request;

interface OnlinePayment{

    public function send(Request $request, ShopBasket $basket);

    public function confirm(Request $request, ShopBasket $baskets, Product $products);

    public function execute(Request $request, ShopOrder $orders, ShopBasket $baskets, Customer $customers);

    public function redirect(Request $request, ShopOrder $orders, $msg);
}