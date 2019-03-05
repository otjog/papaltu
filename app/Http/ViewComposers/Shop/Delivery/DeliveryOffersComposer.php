<?php

namespace App\Http\ViewComposers\Shop\Delivery;

use App\Models\Shop\Order\Shipment;
use Illuminate\View\View;

class DeliveryOffersComposer{

    protected $shipmentData = [
        'shipment' => [
            'services' => [],
        ]
    ];

    protected $shipment;


    public function __construct(Shipment $shipment){
        $this->shipment = $shipment;
        $this->shipmentData['shipment']['services'] = $this->shipment->getDeliveryServices();

    }

    public function compose(View $view){
        $view->with($this->shipmentData);
    }
}