<?php

namespace App\Http\Controllers;

class HomeController extends Controller{

    protected $categories;
    protected $data;

    public function __construct(){
        $this->data = [
            'template' => []
        ];
    }

    public function index(){

        $this->data['template'] ['banner']  = 'default';

        $this->data['template'] ['custom'][]  = 'shop-icons';

        return view( 'templates.default', $this->data);
    }

}
