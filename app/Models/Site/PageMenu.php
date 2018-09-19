<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;

class PageMenu extends Model{

    public function pages(){
        return $this->belongsToMany('App\Models\Site\Page', 'page_menu_has_page')->withTimestamps();
    }

    public function getActiveMenus(){
        $menus =  self::select(
            'page_menus.alias   as menu_alias',
            'pages.id           as id',
            'pages.name         as name',
            'pages.alias        as alias'
        )
            ->where('page_menus.active', 1)

            ->leftJoin('page_menu_has_page', function ($join) {
                $join->on('page_menu_has_page.menu_id', '=', 'page_menus.id');
            })
            ->leftJoin('pages', 'page_menu_has_page.page_id', '=', 'pages.id')

            ->get();

        return $this->getPageMenuTree($menus);

    }

    private function getPageMenuTree($menus){
        $temporary = [];

        foreach ($menus as $menu){
            $temporary[$menu->menu_alias][] = $menu;
        }

        return $temporary;

    }
}
