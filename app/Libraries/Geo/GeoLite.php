<?php

namespace App\Libraries\Geo;

use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;
use GeoIp2\Exception\AddressNotFoundException;

class GeoLite {

    private $reader;

    public function __construct(){

        try{

            $this->reader = new Reader( public_path('storage/geolite/GeoLite2-City.mmdb') );

        } catch (InvalidDatabaseException $exception){

            return $exception;

        }

        return true;

    }

    public function getGeoCity($ip_address){

        try{

            $city = $this->reader->city($ip_address);

        } catch ( AddressNotFoundException $exception){

            return $exception;

        }

        return $city;

    }

}