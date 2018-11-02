<div class="col-12 col-lg-4 pt-3 px-0 shadow rounded">

    <div class="row px-3">
        <div class="col-9">
            <h4>Товары недели!</h4>
        </div>
        <div class="col-3">
            <a href="#carouselDealWeek" role="button" data-slide="prev"><i class="fas fa-angle-left"></i></a>
            <a href="#carouselDealWeek" role="button" data-slide="next"><i class="fas fa-angle-right"></i></a>
        </div>
    </div>

    <div id="carouselDealWeek" class="carousel slide" data-ride="carousel">

        <div class="carousel-inner">
            @foreach($products as $product)
                <div class="carousel-item @if ($loop->first) active @endif" style="padding: 0 15px">
                    <a class="d-block pb-2" href="{{ route( 'products.show', $product->id ) }}">

                        @isset( $product->images[0] )
                            <img class="img-fluid mx-auto d-block" src="{{ URL::asset('storage/img/shop/product/'.$product->images[0]->src) }}">
                        @endisset

                        @empty( $product->images[0] )
                            <div class="text-center text-light"><i class="fas fa-shopping-basket fa-7x"></i></div>
                        @endempty

                        <span class="text-dark">
                            @isset($product->manufacturer['name'])
                                {{ $product->manufacturer['name'] . ' ' }}
                            @endisset

                            {{ $product->name }}

                            @if( isset($product->brands) && count($product->brands) > 0 && $product->brands !== null)

                                @foreach($product->brands as $brand)
                                    {{ ' | ' . $brand->name}}
                                @endforeach

                            @endif
                        </span>
                    </a>

                    @if( isset($product->price['value']) && $product->price['value'] !== null)
                        <div class="row">
                            <div class="col-6 bg-orange rounded-bottom-left text-center py-2 px-1 text-light">
                                Успей купить всего за:
                            </div>

                            <div class="col-6 price bg-primary rounded-bottom-right text-center py-2 text-light">
                                <div class="row">

                                    @if( isset($product->price['sale']) && $product->price['sale'] > 0)
                                        <div class="col-6 px-1 text-danger">
                                        <span>
                                            <s>
                                                {{$product->price['value'] + $product->price['sale']}}<small>{{$components['shop']['currency']['symbol']}}</small>
                                            </s>
                                        </span>
                                        </div>
                                    @endif

                                    <div class="col-auto px-1">
                                        <strong>{{ $product->price['value']}}</strong><small>{{$components['shop']['currency']['symbol']}}</small>
                                    </div>
                                </div>

                            </div>

                            <div class="col-12 d-none">
                                dddd
                            </div>

                        </div>
                    @endif
                </div>
            @endforeach
        </div>

    </div>

</div>