<div class="row">
    <div class="col-12 col-lg-6">
        <div
                id="delivery-offers"
                data-product-width="{{$product->width}}"
                data-product-height="{{$product->height}}"
                data-product-length="{{$product->length}}"
                data-product-weight="{{$product->weight}}"
                data-product-quantity="1"
        >
            @include($global_data['project_data']['template_name'] . '.modules.delivery.templates.offers.show')
        </div>
    </div>
    <div class="col-12 col-lg-6">
        @include($global_data['project_data']['template_name'] . '.modules.delivery.templates.offers.points-on-map')
    </div>
</div>