<?php

namespace App\Http\Controllers\MainPage;

use App\Http\Controllers\Controller;
use App\Category;
use App\Brand;

class MainPageController extends Controller{

    protected $categories;
    protected $data;

    public function __construct(Category $categories){
        $this->categories = $categories;
        $this->data = [
            'template' => []
        ];
    }

    public function index(Brand $brands){

        $this->data['template'] ['banner']  = 'default';
        $this->data['data']     ['brands']  =  $brands->getActiveBrands();

        $this->data['template'] ['custom'][]  = 'shop-icons';

        return view( 'templates.default', $this->data);
    }

}
