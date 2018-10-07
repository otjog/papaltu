<?php

namespace App\Models;

use App\Models\Shop\Price\Currency;
use App\Models\Shop\Price\Price;

class Settings {

    private static $instance = null;

    private $currency;

    private $price;

    public static function getInstance(){
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct(){

        $this->currency = new Currency();

        $this->price = new Price();

        $this->data = [

            'template_name' => env('SITE_TEMPLATE'),
            'info' => [
                'email' => env('SITE_EMAIL'),
                'phone' => env('SITE_PHONE'),
                'address' => env('SITE_ADDRESS'),
            ],
            'components' => [
                'site' => [],
                'shop' => [
                    'currency' =>
                        $this->currency
                            ->select('id', 'char_code', 'symbol')
                            ->where('main', '1')
                            ->first(),
                    'price'    =>
                        $this->price
                            ->select('id', 'name')
                            ->where('name', 'retail')
                            ->first(),
                    'pagination' => 15,
                    'chunk_products' => 3,
                    'chunk_categories' => 4,
                    'filter_prefix' => 'p_'
                ]
            ],
            'today' => date('Y-m-d')
        ];

    }

    private function __clone(){}

    public function getParameters(){
       return $this->data;
    }

    public function getParameter($path){

        $data = $this->getParameters();

        $pathArray = explode('.', $path);

        $temporary = [];

        foreach( $pathArray as $key => $level ){

            if($key === 0)
                $temporary = $data;

            if($key+1 === count($pathArray) )
                return $this->getLevel($temporary, $level);
            else
                $temporary = $this->getLevel($temporary, $level);
        }

    }

    private function getLevel($array, $level){

        return $array[ $level ];

    }

}