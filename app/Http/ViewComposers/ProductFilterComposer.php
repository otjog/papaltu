<?php

namespace App\Http\ViewComposers;

use App\Filter;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductFilterComposer{

    protected $filters;

    public function __construct(Request $request, Filter $filters, Product $products){
        $this->filters = $filters->getActiveFilters($request, $products);
    }

    public function compose(View $view){
        $view->with('filters', $this->filters);
    }
}