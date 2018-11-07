<?php

namespace App\Http\Controllers;

use App\Models\Settings;

class HomeController extends Controller{

    protected $categories;

    protected $data;

    public function __construct(){

        $settings = Settings::getInstance();

        $this->data = $settings->getParameters();

        $this->data['template'] = [];
    }

    public function index(){

        $this->data['template'] ['banner']  = 'default';

        //  $this->data['template'] ['modules']['custom']  = 'shop-icons';

        $this->data['template'] ['modules']['offers']  = 'default';

        return view( 'templates.default', $this->data);
    }

}
