<?php

namespace App\Models;

use App\Shipment;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Delivery\Dpd;
use App\Libraries\Delivery\Cdek;
use App\Models\GeoData;

class DeliveryServices extends Model{

    private $geoData;

    private $services;

    public function __construct(array $attributes = []){

        parent::__construct($attributes);

        $shipments = new Shipment();

        $this->services = $shipments->getDeliveryServices();

        $geoData = new GeoData();

        $this->geoData = $geoData->getGeoData();
    }

    public function getPrices($parcelParameters){

            $data = [];

            foreach($this->services as $services){

                switch($services->alias){

                    case 'dpd'  : $serviceObj = new Dpd( $this->geoData ); break;

                    case 'cdek' : $serviceObj = new Cdek( $this->geoData ); break;

                }

                $data['costs'][$services->alias]  = $serviceObj->getSelfAndToDoorServiceCost($parcelParameters);

                $data['servicesInfo'][$services->alias] = $services;
            }

            $data['_bestOffer'] = $this->pullBestPrice($data);

            $data['_geo'] = $this->geoData;

            return $data;
    }

    public function getBestPrices($parcelParameters){

            $data = [];

            $temporary = [];

            foreach($this->services as ['alias' => $serviceAlias]){

                switch($serviceAlias){

                    case 'dpd'  : $serviceObj = new Dpd( $this->geoData ); break;

                    case 'cdek' : $serviceObj = new Cdek( $this->geoData ); break;

                }

                $temporary['costs'][$serviceAlias]  = $serviceObj->getSelfAndToDoorServiceCost($parcelParameters);
            }

            $data['_bestOffer'] = $this->pullBestPrice($temporary);

            $data['_geo'] = $this->geoData;

            return $data;
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

                    $cost->company = $company;

                    $cost->deliveryTo = $delTo;

                    $offers[] = $cost;

                }

            }

            $cost = array_column($offers, 'cost');

            $days = array_column($offers, 'days');

            array_multisort(
                $cost, SORT_ASC, SORT_NUMERIC,
                $days, SORT_ASC, SORT_NUMERIC,
                $offers
            );

            return array_shift($offers);
        }

}