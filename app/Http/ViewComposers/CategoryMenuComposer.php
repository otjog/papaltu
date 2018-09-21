<?php

namespace App\Http\ViewComposers;

use App\Models\Shop\Category\Category;
use Illuminate\View\View;

class CategoryMenuComposer{

    protected $categories;

    public function __construct(Category $categories){
        $this->categories = $categories;
    }

    public function compose(View $view){
        $view->with('categories', $this->categories->getCategoriesTree());
    }
}