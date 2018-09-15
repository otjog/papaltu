@if(isset($categories) && count($categories) > 0)
    @switch($menu_type)
        @case('dropdown')   @include('modules.menu.dropdown',   ['list' => $categories, 'path' => 'categories'])     @break
        @case('list')       @include('modules.menu.list',       ['list' => $categories, 'path' => 'categories'])     @break
    @endswitch
@endif