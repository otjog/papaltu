<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\Geo\GeoLite;

class GeoData extends Model{

    public function getGeoData($session){

        return $session->get('geoInput', function() use ($session){

            return $session->get('geoIp');

        });
    }

    public function setGeoInput($json){

        $objectData = json_decode($json);

        $geoData = [
            'postal_code'   => $objectData->data->postal_code,
            'country_code'  => $this->getCountryCode($objectData->data->country),
            'country_name'  => $objectData->data->country,
            'region_code'   => substr($objectData->data->region_kladr_id, 0, 2),
            'region_name'   => $objectData->data->region,
            'region_type'   => $objectData->data->region_type,
            'area_name'     => $objectData->data->area,
            'area_type'     => $objectData->data->area_type,
            'city_name'     => $objectData->data->city,
            'city_type'     => $objectData->data->city_type,
            'city_kladr_id' => $objectData->data->city_kladr_id,
            'street_name'   => $objectData->data->street,
            'street_type'   => $objectData->data->street_type,
            'latitude'      => $objectData->data->geo_lat,
            'longitude'     => $objectData->data->geo_lon,
        ];

        if($geoData['city_name'] === null){
            $geoData['city_name']       = $objectData->data->settlement;
            $geoData['city_type' ]      = $objectData->data->settlement_type;
            $geoData['city_kladr_id']   = $objectData->data->settlement_kladr_id;
        }

        session(['geoInput' => $geoData]);

    }

    public function setGeoIp(){

        $ipAddress = $_SERVER['REMOTE_ADDR'];

        if($_SERVER['REMOTE_ADDR'] === '127.0.0.1'){

            //$ipAddress = '213.87.147.113'; //Москва
            //$ipAddress = '178.216.79.66'; //Белгород
            $ipAddress = '92.37.241.243'; //Комсомольск-на-Амуре (Хабаровский край)

        }

        $geolite = new GeoLite();

        $objectData = $geolite->getGeoCity($ipAddress);

        $geoData = [
            'postal_code'   => $objectData->postal->code,
            'country_code'  => $objectData->raw['country']['iso_code'],
            'country_name'  => $objectData->country->names['ru'],
            'city_name'     => $objectData->city->names['ru'],
            'latitude'      => $objectData->location->latitude,
            'longitude'     => $objectData->location->longitude,
        ];

        $regionData = $this->getRegionData($objectData->subdivisions[0]->names['ru']);

        $geoData = array_merge( $geoData, $regionData );

        session(['geoIp' => $geoData]);

    }

    private function getCountryCode($countryName){
        switch($countryName){
            case 'Россия'   : return 'RU';
            case 'Украина'  : return 'UA';
        }
    }

    private function getRegionData($fullRegionName){

        $regionTypes = [
            'АО' => 'АО', 'край' => 'край', 'Аобл' => 'Автономная область', 'обл' => 'область'
        ];

        $data = ['region_name' => $fullRegionName];

        foreach($regionTypes as $key => $type){
           if(strstr($fullRegionName, ' '.$type) !== false){
               $data['region_name'] = strstr($fullRegionName, ' '.$type, true);
               $data['region_type'] = $key;
           }
        }

        return $data;
    }

}
