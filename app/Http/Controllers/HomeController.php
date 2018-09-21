<?php

namespace App\Http\Controllers;

class HomeController extends Controller{

    protected $categories;

    protected $data;

    protected $template_name;

    public function __construct(){

        $this->template_name = env('SITE_TEMPLATE');

        $this->data = [
            'template' => [],
            'template_name' => $this->template_name
        ];
    }

    public function index(){

        $this->data['template'] ['banner']  = 'default';

        $this->data['template'] ['custom'][]  = 'shop-icons';

        return view( 'templates.default', $this->data);
    }

}
