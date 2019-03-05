<?php

namespace App\Http\Controllers;

use App\Models\Settings;

class HomeController extends Controller{

    protected $categories;

    protected $data = [];

    protected $settings;

    public function __construct(){

        $this->settings = Settings::getInstance();

        $this->data['template'] = [];
    }

    public function index(){

        $this->data['global_data']['project_data'] = $this->settings->getParameters();

        $this->data['template'] ['banner']  = 'default';

        //  $this->data['template'] ['modules']['custom']  = 'shop-icons';

        $this->data['template'] ['modules']['offers']  = 'default';

        return view( 'templates.default', $this->data);
    }

}
