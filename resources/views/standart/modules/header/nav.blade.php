<nav class="main_nav">
    <div class="container">
        <div class="row">
            <div class="col-12">

                <div class="main_nav_content d-flex flex-row">

                    <!-- Categories Menu -->
                    @include( $template_name .'.modules.menu.shop', ['menu_type' => 'dropdown'])

                    <!-- Main Nav Menu -->
                    @include( $template_name .'.modules.menu.page', ['menu_type' => 'line', 'menu_name' => 'top_menu'])

                </div>
            </div>
        </div>
    </div>
</nav>