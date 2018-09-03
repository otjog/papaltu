<?php

namespace App\Libraries\Delivery;

use SoapClient;
use Exception;

class Dpd {

    private $soapClient;

    private $clientNumber       = '1051001516';

    private $clientKey          = '8491BA30A5AE7FBBEEFD7099D9B6249358478A94';

    private $pickUpCity         = 'Белгород';

    private $pickUpRegionCode   = '31';

    private $pickUpCountryCode  = 'RU';

    private $test = 0;

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

    private $destinationTypes = [

        'toTerminal' => [
            'active' => 1,
            'selfDelivery' => true,
        ],

        'toDoor' => [
            'active' => 1,
            'selfDelivery' => false,
        ]
    ];

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
            'delivery' => $this->geoData,
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

        if(isset($services->parcelShop))
            return $services->parcelShop;

        return $services;

    }

    public function getTerminals(){

        $services = $this->getDpdData( 'getTerminalsSelfDelivery2' );

        return $services->terminal;

    }

    public function getSelfAndToDoorServiceCost($parcelParameters){

        $data = [];

        foreach($this->destinationTypes as $type => ['active' => $active, 'selfDelivery' => $selfDelivery]){

            if($active){

                $services = $this->getServiceCost($parcelParameters, $selfDelivery);

                if( count($services) > 0 ){

                    $data[$type] = $this->getOptimalService($services);

                }

            }

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

            $this->message = $ex->getMessage();

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

                $this->setErrorMessage( $ex , $data);

                return [];
            }

            return $object->return;

        }else{
            //не смогли подключиться к dpd
            return false;//todo нужно выводить сообщение об ошибке, если не удалось подключиться к dpd

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
                case 'city_kladr_id': $this->geoData['cityCode']    = $paramValue; break;
                case 'city_name'    : $this->geoData['cityName']    = $paramValue; break;
                case 'city_id'      : $this->geoData['cityId']      = $paramValue; break; //cityId - DPD
                case 'postal_code'  : $this->geoData['index']       = $paramValue; break;
            }

        }

    }

    private function setErrorMessage(Exception $ex, $data){

        switch($ex->getCode()){
            case 'no-service-available' :
                $this->message = 'Невозможно выполнить доставку по маршруту ' . $data['pickup']['cityName'] . ' - ' . $data['delivery']['cityName'] . $this->destinationDelivery($data['selfDelivery']) . '.'; break;
        }

    }

    private function destinationDelivery($selfDelivery){
        if($selfDelivery)
            return ' до пункта выдачи';
        else
            return ' до адреса клиента';
    }

}
