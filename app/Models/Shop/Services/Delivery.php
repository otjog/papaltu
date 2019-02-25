<?php

namespace App\Models\Shop\Services;

use App\Models\Shop\Order\Shipment;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Delivery\Dpd;
use App\Libraries\Delivery\Cdek;
use App\Libraries\Delivery\Pochta;
use App\Models\Settings;

class Delivery extends Model{

    private $geoData;

    private $services;

    private $shipments;

    private $serviceTypes = [ 'toTerminal', 'toDoor' ];

    public function __construct(array $attributes = []){

        parent::__construct($attributes);

        $this->shipments = new Shipment();

        $this->services = $this->shipments->getDeliveryServices();

        $settings = Settings::getInstance();

        $this->geoData = $settings->getParameter('geo');

    }

    public function getPrices($request){

        list($parcelParameters, $deliveryServiceAlias) = $this->getDeliveryDataFromRequest($request);

        $data = [
            'costs' => [],
        ];

        switch($deliveryServiceAlias){

            case 'dpd'      :
                $serviceObj = new Dpd( $this->geoData );
                $serviceTypes = $this->serviceTypes;
                break;

            case 'cdek'     :
                $serviceObj = new Cdek( $this->geoData );
                $serviceTypes = $this->serviceTypes;
                break;

            case 'pochta'   :
                $serviceObj = new Pochta( $this->geoData );
                $serviceTypes = ['toTerminal'];
                break;

            default : break; //todo сделать выход из foreach

        }

        $costs = $serviceObj->getDeliveryCost($parcelParameters, $serviceTypes);

        if( count($costs) > 0 ){

            $data['costs'] = $costs;

        }

        return $data;

    }

    public function getPoints($request){

        list($parcelParameters, $deliveryServiceAlias) = $this->getDeliveryDataFromRequest($request);

        $data = [];

        $serviceObj = null;

        switch($deliveryServiceAlias){

            case 'dpd'  : $serviceObj = new Dpd( $this->geoData ); break;

            case 'cdek' : $serviceObj = new Cdek( $this->geoData ); break;

        }

        if($serviceObj !== null){
            $data['points'][$deliveryServiceAlias] = $serviceObj->getPointsInCity();
        }

        return $data;

    }

    public function getDeliveryDataFromRequest($request){

        if( count($request) > 0 ){
            $parcels = [];
            $deliveryServiceAlias = null;

            foreach($request as $name => $params) {

                if($name === 'dsalias'){
                    $deliveryServiceAlias = $params;
                }else{

                    $arr = explode('|', $params);

                    foreach ($arr as $key => $param) {

                        $parcels[$key][$name] = $param;

                    }

                }

            }
        }

        return [$parcels, $deliveryServiceAlias];

    }

}