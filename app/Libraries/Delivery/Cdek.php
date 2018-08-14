<?php

namespace App\Libraries\Delivery;

use SoapClient;
use Exception;

class Cdek {

    private $soapClient;

    private $clientNumber       = '1051001516';

    private $clientKey          = '8491BA30A5AE7FBBEEFD7099D9B6249358478A94';

    private $pickUpCity         = 'Белгород';

    private $pickUpRegionCode   = '31';

    private $pickUpCountryCode  = 'RU';

    private $test = 1;

    private $dpdHosts = [
        0 => 'http://ws.dpd.ru/services/', //рабочий хост
        1 => 'http://wstest.dpd.ru/services/' //тестовый хост
    ];

    private $dpdServices = [
        //список городов с возможностью доставки с нал.платежом
        'getCitiesCashPay'              => [
            'service_name'  => 'geography2',
            'request'       => true
        ],
        //список пунктов, имеющих ограничения, с указанием режима работы.
        'getParcelShops'                => [
            'service_name'  => 'geography2',
            'request'       => true
        ],
        //список подразделений DPD, не имеющих ограничений
        'getTerminalsSelfDelivery2'     => [
            'service_name'  => 'geography2',
            'request'       => false
        ],
        //список сервисов: стоимость доставки по параметрам  посылок по России и странам ТС.
        'getServiceCostByParcels2'      => [
            'service_name'  => 'calculator2',
            'request'       => true
        ],
    ];

    private $message;

    private $geoData;

    public function __construct($geoData){

        $this->prepareGeoData($geoData);

    }

    public function getServiceCost($parcelParameters, $selfDelivery = true, $serviceCode = null){

        $data = [
            'pickup' => [
                'cityName'      => $this->pickUpCity,
                'regionCode'    => $this->pickUpRegionCode,
                'countryCode'   => $this->pickUpCountryCode
            ],
            'delivery' => [
                'cityName' => $this->geoData['cityName'],
                'index' => $this->geoData['index'],
                'countryCode' => $this->geoData['countryCode']
            ],
            'selfPickup' => true, //Доставка от терминала
            'selfDelivery' => $selfDelivery, //Доставка До терминала
            'parcel' => $parcelParameters,
        ];

        if($serviceCode !== null){
            $data['serviceCode'] = $serviceCode;
        }

        $services = $this->getDpdData( 'getServiceCostByParcels2', $data );

        return $services;

    }

    public function getPointsInCity(){

        $points = [];

        $points['shops'] =  $this->getParcelShops();

        $points['terminals'] =  $this->getTerminals();

        return $points;

    }

    public function getParcelShops(){

        $data = [
            'countryCode'   => $this->geoData['countryCode'],
            'regionCode'    => $this->geoData['regionCode']
        ];

        $services = $this->getDpdData( 'getParcelShops', $data );

        return $services->parcelShop;

    }

    public function getTerminals(){

        $services = $this->getDpdData( 'getTerminalsSelfDelivery2' );

        return $services->terminal;

    }

    public function getSelfAndToDoorServiceCost($parcelParameters){

        $services = $this->getServiceCost($parcelParameters, true);
        if( count($services) > 0 ){
            $data['toTerminal'] = $this->getOptimalService($services);
        }

        $services = $this->getServiceCost($parcelParameters, false);
        if( count($services) > 0 ){
            $data['toDoor']     = $this->getOptimalService($services);
        }

        return $data;
    }

    private function connectDpd( $method_name ){

        $service = $this->dpdServices[$method_name]['service_name'];

        if ( !$service ) {

            $this->message = 'В свойствах класса нет сервиса "' . $method_name . '"';

            return false;
        }

        $host = $this->dpdHosts[$this->test] . $service . '?WSDL';

        try {

            $this->soapClient = new SoapClient( $host , [ 'exceptions' => 1 ]);

        } catch ( Exception $ex ) {

            $this->message = $ex;

            return false;
        }

        return true;
    }

    private function getDpdData( $method_name, $data = [] ){

        if ( $this->connectDpd( $method_name ) ){

            $data['auth'] = [
                'clientNumber' => $this->clientNumber,
                'clientKey' => $this->clientKey
            ];

            if ( $this->dpdServices[$method_name]['request'] ){

                $request['request'] = $data;

            }else{

                $request = $data;

            }

            try {

                $object = $this->soapClient->$method_name( $request );

            } catch ( Exception $ex ) {

                $this->message = $ex;

                return [];
            }

            return $object->return;

        }else{
            //не смогли подключиться к dpd
            return false;

        }

    }

    private function getOptimalService($services){

        $cost = array_column($services, 'cost');

        $days = array_column($services, 'days');

        array_multisort($cost, SORT_ASC, $days, SORT_ASC, $services);

        return $services[0];
    }

    private function prepareGeoData($geoData){

        foreach($geoData as $paramName => $paramValue){

            switch($paramName){
                case 'country_code' : $this->geoData['countryCode'] = $paramValue; break;
                case 'region_code'  : $this->geoData['regionCode']  = $paramValue; break;
                case 'city_code'    : $this->geoData['cityCode']    = $paramValue; break;
                case 'city_name'    : $this->geoData['cityName']    = $paramValue; break;
                case 'city_id'      : $this->geoData['cityId']      = $paramValue; break;
                case 'postal_code'  : $this->geoData['index']       = $paramValue; break;
            }

        }

    }

}
