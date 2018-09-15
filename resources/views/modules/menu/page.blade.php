@if( isset( $menus[$menu_name] ) && count( $menus[$menu_name] ) > 0 )
    @switch($menu_type)

        @case('line')   @include('modules.menu.line', ['list' => $menus[$menu_name], 'path' => 'pages']) @break

        @case('list')   @include('modules.menu.list', ['list' => $menus[$menu_name], 'path' => 'pages']) @break

    @endswitch
@endif