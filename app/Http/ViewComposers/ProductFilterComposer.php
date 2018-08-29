<?php

namespace App\Http\ViewComposers;

use App\Filter;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductFilterComposer{

    protected $filters;
    protected $route_value;

    public function __construct(Request $request, Filter $filters, Product $products){
        $this->filters = $filters->getActiveFilters($request, $products);
        $this->route_value = $request->route()->parameters;
    }

    public function compose(View $view){
        $view->with(['filters' => $this->filters, 'route_value' => $this->route_value]);
    }
}