<?php

namespace App\Models\Shop\Services;

use App\Models\Shop\Order\Shipment;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Delivery\Dpd;
use App\Libraries\Delivery\Cdek;
use App\Libraries\Delivery\Pochta;
use App\Models\Geo\GeoData;

class Delivery extends Model{

    private $geoData;

    private $services;

    private $shipments;

    private $serviceTypes = [ 'toTerminal', 'toDoor' ];

    public function __construct(array $attributes = []){

        parent::__construct($attributes);

        $this->shipments = new Shipment();

        $this->services = $this->shipments->getDeliveryServices();

        $geoData = new GeoData();

        $this->geoData = $geoData->getGeoData();
    }

    public function getPrices($parcelParameters){

            $data = [
                'costs' => [],
            ];

            foreach($this->services as $services){

                switch($services->alias){

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

                    $data['costs'][$services->alias]  = $costs;

                    $data['shipments'][$services->alias] = $services;
                }

            }

            if( count( $data['costs']) > 0 ){
                $data['_bestOffer'] = $this->pullBestPrice($data);
            }else{
                $data['shipments'] = $this->shipments->getDefaultShipments();
            }

            $data['_geo'] = $this->geoData;

            return $data;
    }

    public function getBestPrice($parcelParameters){

        return $this->getPrices($parcelParameters);
    }

    public function getPoints(){

        $data = [];

        foreach($this->services as ['alias' => $serviceAlias]){

            switch($serviceAlias){

                case 'dpd'  : $serviceObj = new Dpd( $this->geoData ); break;

                case 'cdek' : $serviceObj = new Cdek( $this->geoData ); break;

            }

            $data['points'][$serviceAlias] = $serviceObj->getPointsInCity();

        }

        $data['_geo'] = $this->geoData;

        return $data;
    }

    private function pullBestPrice($data){

            $offers = [];

            foreach($data['costs'] as $company => $parameters){

                foreach($parameters as $delTo => $cost){

                    $cost['company']    = $company;

                    $cost['deliveryTo'] = $delTo;

                    $offers[] = $cost;

                }

            }

            $cost = array_column($offers, 'price');

            $days = array_column($offers, 'days');

            array_multisort(
                $cost, SORT_ASC, SORT_NUMERIC,
                $days, SORT_ASC, SORT_NUMERIC,
                $offers
            );

            return array_shift($offers);
        }

}