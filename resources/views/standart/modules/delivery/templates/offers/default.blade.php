<div
        id="delivery-offers"
        data-product-width="{{$product->width}}"
        data-product-height="{{$product->height}}"
        data-product-length="{{$product->length}}"
        data-product-weight="{{$product->weight}}"
        data-product-quantity="1"
>
    @include($template_name. '.modules.delivery.templates.offers.' . $delivery_template)
</div>