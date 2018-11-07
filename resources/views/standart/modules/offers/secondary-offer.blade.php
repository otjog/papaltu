<div class="col-12 col-lg-7 offset-lg-1">

    <ul class="nav pb-2" id="tabs">
        @foreach($offers as $offer)
            @if( isset($offer->products) && count($offer->products) > 0)
                <li class="nav-item">
                    <span data-tabIndex="{{$offer->name}}"  class="nav-link @if ($loop->first) active @endif">{{$offer->header}}</span>
                </li>
            @endif
        @endforeach
    </ul>

    <div id="tab-data">
        @foreach($offers as $offer)
            {{-- Products Tab --}}
            <div class="tab-data data-{{$offer->name}}">
            @if(isset($offer->products) && count($offer->products) > 0)

                @foreach($offer->products->chunk(3) as $products_row)

                    <div class="row no-gutters">

                        @foreach( $products_row as $key => $product )

                            <div class="product-item-wrap col-4 rounded-0">
                                <!--span class="bg-light small px-1 py-1 w-50 rounded-bottom-right">Артикул: {{$product->scu or ''}}</span-->
                                <div class="product-item border-right border-bottom py-1 px-2">

                                    <!-- Image-->
                                    <a href="{{ route( 'products.show', $product->id ) }}">

                                        @isset( $product->thumbnail )
                                            <img class="img-fluid mx-auto d-block" src="{{ URL::asset('storage/img/shop/product/thumbnail/'.$product->thumbnail) }}">
                                        @endisset

                                        @empty( $product->thumbnail )
                                            <div class="text-center text-light"><i class="fas fa-shopping-basket fa-7x"></i></div>
                                        @endempty
                                    </a>

                                    <!--Price-->
                                    @if( isset($product->price['value']) && $product->price['value'] !== null)
                                        <div class="bg-white py-3">
                                            <div class="row">
                                                <div class="col-12 price category-product text-center">
                                                    <div class="row">
                                                        @if( isset($product->price['sale']) && $product->price['sale'] > 0)
                                                            <div class="col-6">
                                                                <span class="small">
                                                                    <s>
                                                                        {{$product->price['value'] + $product->price['sale']}}<small>{{$components['shop']['currency']['symbol']}}</small>
                                                                    </s>
                                                                </span>
                                                            </div>
                                                        @endif

                                                        <div class="col text-danger">
                                                            <span>{{ $product->price['value']}}</span><small>{{$components['shop']['currency']['symbol']}}</small>
                                                        </div>
                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    @endif

                                    <!--Name-->
                                    <a class="product-name text-dark d-none" href="{{ route( 'products.show', $product->id ) }}">
                                            <u>
                                                @isset($product->manufacturer['name'])
                                                    {{ $product->manufacturer['name'] . ' ' }}
                                                @endisset
                                                {{ $product->name }}
                                                @if( isset($product->brands) && count($product->brands) > 0 && $product->brands !== null)
                                                    @foreach($product->brands as $brand)
                                                        {{ ' | ' . $brand->name}}
                                                    @endforeach

                                                @endif
                                            </u>
                                    </a>

                                    <div class="product-action w-100 d-none text-center border-top mt-2">

                                        @if( !isset($product->basket_parameters) || count($product->basket_parameters) === 0)
                                            <form method="post" role="form" action="{{route('baskets.store')}}">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="product_id"       value="{{ $product->id}}">
                                                <input type="hidden" name="quantity" value="1" >
                                                <input class="btn btn-link pb-0" type="submit" value="В корзину" />
                                            </form>
                                        @else
                                            <a class="pt-2 d-block" href="{{ route( 'products.show', $product->id ) }}">
                                                Выбрать размер
                                            </a>
                                        @endif


                                    </div>

                                </div>

                            </div>
                        @endforeach
                    </div>
                @endforeach

            @endif
        </div>
        @endforeach

    </div>

</div>