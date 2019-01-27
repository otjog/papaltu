<?php

namespace App\Models;

use App\Models\Shop\Price\Currency;
use App\Models\Shop\Price\Price;

class Settings {

    private static $instance = null;

    private $currency;

    private $price;

    public $data;

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
            'site_url' => env('APP_URL'),
            'info' => [
                'email' => env('SITE_EMAIL'),
                'phone' => env('SITE_PHONE'),
                'address' => env('SITE_ADDRESS'),
            ],
            'general' => [
                'images' => [
                    'path' => 'storage/img/',
                    'const_ext' => 2 //сохранять миниатюры в jpeg
                ]
            ],
            'components' => [
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
                    'filter_prefix' => 'p_',
                    'images' => [
                        'size' => [
                            'xxs'   => '55x55',
                            'xs'    => '130x130',
                            's'     => '240x240',
                            'm'     => '450x450',
                            'm-13'  => '450x600', //W*1 x H*1.3
                            'l'     => '1000x1000',
                        ],
                        'original_folder' => '', //со слешем, ex.: original/
                        'default_name'  => 'no-image.jpg'
                    ],
                    'path_to_image' => public_path('storage/img/shop/product/'),

                ]
            ],
            'today' => date('Y-m-d')

        ];

    }

    private function __clone(){}

    public function addParameter($name, $value){
        $this->data[$name] = $value;
    }

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