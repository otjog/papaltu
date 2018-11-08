<div class="product-list" id="product-list">
    <div class="container">

        @if(isset($products) && count($products) > 0)

            @foreach($products->chunk($components['shop']['chunk_products']) as $products_row)

                @include($template_name .'.components.shop.product.elements.product_rows.light')

            @endforeach
        @endif
    </div>

@if( isset($products) && count($products) > 0)
        <!-- Shop Page Navigation -->
        @include($template_name .'.modules.pagination.default')
    @endif

</div>