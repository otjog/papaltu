@if( isset( $product->images ) && count( $product->images ) > 1)
    <!-- List -->
    <div class="col-lg-3 order-lg-1 order-2">
        <ul class="image_list">
            @foreach( $product->images as $image)

                <li>
                    <a class="fancybox" rel="product_images" href="{{ URL::asset('storage/img/shop/product/l/' . $image->src) }}" title="">
                        <img
                                src="{{ URL::asset('storage/img/shop/product/xs/' . $image->src) }}"
                                alt=""
                        />
                    </a>
                </li>

            @endforeach
        </ul>
    </div>

@endif

<!-- Main Image -->
<div class="col-lg-9 order-lg-2 order-1">
    <div class="image_selected">
        @if( isset($product->images[0]->src) && $product->images[0]->src !== null)
            <a class="fancybox" rel="product_images" href="{{ URL::asset('storage/img/shop/product/l/' . $product->images[0]->src) }}" title="">
                <img
                        src="{{ URL::asset('storage/img/shop/product/m/' . $product->images[0]->src) }}"
                        alt=""
                />
            </a>
        @else
            <img
                    src="{{ URL::asset('storage/img/shop/default/m/' . $components[$template['component']]['images']['default_name']) }}"
                    alt=""
            />
        @endif
    </div>
</div>