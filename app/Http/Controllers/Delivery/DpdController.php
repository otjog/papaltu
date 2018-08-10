<?php

namespace App\Http\Controllers\Delivery;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SoapClient;
use GeoIp2\Database\Reader;

class DpdController extends Controller{

    public function getPrice(){

        $client = new SoapClient('http://wstest.dpd.ru/services/calculator2?wsdl');

        $data['request'] = [
            'auth' => [
                'clientNumber' => '1051001516',
                'clientKey' => '8491BA30A5AE7FBBEEFD7099D9B6249358478A94'
            ],
            'pickup' => [
                //'cityId' => '',
                'cityName' => 'Белгород',
                'regionCode' => '31',
                'countryCode' => 'RU'
            ],
            'delivery' => [
                'cityId' => '49694102',
                'cityName' => 'Москва',
                //'index' => '',
                'regionCode' => '77',
                'countryCode' => 'RU'
            ],
            'selfPickup' => false,
            'selfDelivery' => true,
            //'serviceCode' => '',
            'pickupDate' => '2018-08-10',
            //'maxDays' => '',
           // 'maxCost' => '',
            //'declaredValue' => '',
            'parcel' => [
                'weight' => 0.3,
                'length' => 20,
                'height' => 10,
                'width' => 10,
                'quantity' => 1,
            ],

        ];

        $result = $client->getServiceCostByParcels2( $data );

        dd($result);

    }

    public function getIp(){
        $reader = new Reader( public_path('storage/geolite/GeoLite2-City.mmdb') );

// Replace "city" with the appropriate method for your database, e.g.,
// "country".
        $record = $reader->city($_SERVER['REMOTE_ADDR']);
        
        dd($record);
    }

}
