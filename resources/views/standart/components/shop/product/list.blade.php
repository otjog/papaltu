<div class="product-list" id="product-list">
    <div class="container">

        @if(isset($products) && count($products) > 0)

            @php
                if(isset($product_chunk) === false || is_nan($product_chunk) || $product_chunk > 5){
                    $product_chunk = 3;
                }
            @endphp

            @foreach($products->chunk($product_chunk) as $products_row)

                <div class="card-group">

                    @foreach( $products_row as $key => $product )

                        <div class="card rounded-0">
                            <span class="bg-light small px-1 py-1 w-50 rounded-bottom-right">Артикул: {{$product->scu or ''}}</span>
                            <div class="card-body px-2">
                                <a href="{{ route( 'products.show', $product->id ) }}">

                                    @isset( $product->thumbnail )
                                        <img class="img-fluid mx-auto d-block" src="{{ URL::asset('storage/img/shop/product/thumbnail/'.$product->thumbnail) }}">
                                    @endisset

                                    @empty( $product->thumbnail )
                                        <div class="text-center text-light"><i class="fas fa-shopping-basket fa-7x"></i></div>
                                    @endempty

                                    <span class="card-title text-dark"><u>{{ $product->manufacturer->name . ' ' . $product->name }}</u></span>
                                </a>
                            </div>

                            @isset($product->prices[0]->value)
                                <div class="card-footer bg-white">
                                    <div class="row">
                                        <div class="col-6 price category-product text-left">
                                            <div class="row">
                                                <div class="col-12" style="margin-bottom: -1.2rem">
                                                    @if( isset($product->prices[0]->sale) && $product->prices[0]->sale > 0)
                                                        <span class="small text-danger">
                                                            <s>
                                                                {{$product->prices[0]->value + $product->prices[0]->sale}}<small>руб</small>
                                                            </s>
                                                        </span>
                                                    @endif

                                                </div>

                                                <div class="col-12 pt-3">
                                                    <span>{{ $product->prices[0]->value}}</span><small>руб</small>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="col-6">
                                            <form method="post" role="form" action="{{route('baskets.store')}}">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="id"       value="{{ $product->id}}">
                                                <input type="hidden" name="quantity" value="1" >
                                                <input class="btn btn-danger" type="submit" value="В корзину" />
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endisset

                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- Shop Page Navigation -->

<!--div class="shop_page_nav d-flex flex-row">
    <div class="page_prev d-flex flex-column align-items-center justify-content-center"><i class="fas fa-chevron-left"></i></div>
    <ul class="page_nav d-flex flex-row">
        <li><a href="#">1</a></li>
        <li><a href="#">2</a></li>
        <li><a href="#">3</a></li>
        <li><a href="#">...</a></li>
        <li><a href="#">21</a></li>
    </ul>
    <div class="page_next d-flex flex-column align-items-center justify-content-center"><i class="fas fa-chevron-right"></i></div>
</div-->