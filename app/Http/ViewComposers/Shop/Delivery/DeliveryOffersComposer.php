<?php

namespace App\Http\ViewComposers\Shop\Delivery;

use App\Models\Shop\Order\Shipment;
use Illuminate\View\View;

class DeliveryOffersComposer{

    protected $shipments;

    public function __construct(Shipment $shipments){
        $this->shipments = $shipments;
    }

    public function compose(View $view){
        $view->with('deliveryServices', $this->shipments->getDeliveryServices());
    }
}