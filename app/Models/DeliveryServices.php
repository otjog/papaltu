<?php

namespace App\Models;

use App\Shipment;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Delivery\Dpd;
use App\Libraries\Delivery\Cdek;
use App\Models\GeoData;

class DeliveryServices extends Model{

        private $geoData;

        public function getDeliveryDataForProduct($session, $parcelParameters){

            $shipments = new Shipment();

            $this->services = $shipments->getDeliveryServices();

            $geoData = new GeoData();

            $this->geoData = $geoData->getGeoData($session);

            $data = [];

            foreach($this->services as ['alias' => $serviceAlias]){

                switch($serviceAlias){

                    case 'dpd'  : $serviceObj = new Dpd( $this->geoData ); break;

                    case 'cdek' : $serviceObj = new Cdek( $this->geoData ); break;

                }

                $data[$serviceAlias]['costs']  = $serviceObj->getSelfAndToDoorServiceCost($parcelParameters);

                $data[$serviceAlias]['points'] = $serviceObj->getPointsInCity();

            }

            $data['_bestOffer'] = $this->getBestPriceOffer($data);

            $data['_geo'] = $this->geoData;

            return $data;

        }

        private function getBestPriceOffer($data){

            $offers = [];

            foreach($data as $company => $parameters){

                foreach($parameters['costs'] as $delTo => $cost){

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
