<?php

namespace App\Http\ViewComposers;

use App\Models\Site\PageMenu;
use Illuminate\View\View;

class PageMenuComposer{

    protected $menus;

    public function __construct(PageMenu $menus){
        $this->menus = $menus->getActiveMenus();
    }

    public function compose(View $view){
        $view->with('menus', $this->menus);
    }
}