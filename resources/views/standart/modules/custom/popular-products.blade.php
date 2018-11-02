<div class="popular-products my-3">
    <div class="container">
        <div class="row no-gutters">

                @include( $template_name .'.modules.custom.deal-week', ['products' => $products['deal_week']])

                @include( $template_name .'.modules.custom.featured-products')

        </div>
    </div>
</div>