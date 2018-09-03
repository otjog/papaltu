<?php

namespace App\Libraries\Services\Pay\Contracts;

use App\ShopBasket;
use App\ShopOrder;
use App\Product;
use Illuminate\Http\Request;

interface OnlinePayment{

    public function send(Request $request);

    public function confirm(Request $request, ShopBasket $baskets, Product $products);

    public function execute(Request $request, ShopOrder $orders, ShopBasket $baskets);

    public function redirect(Request $request, ShopOrder $orders, $msg);
}