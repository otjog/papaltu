<div>
    <div class="col-md-4 order-md-2 mb-4">
        <h4 class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">Ваш заказ</span>
        </h4>

        @php
            $sum = 0
        @endphp

        <table class="table">
            <thead class="thead-light">
            <tr>
                <th scope="col">#</th>
                <th scope="col">Наименование</th>
                <th scope="col">Кол-во</th>
                <th scope="col">Стоимость</th>
                <th scope="col">Сумма</th>
            </tr>
            </thead>
            <tbody>

            @if( isset( $data['products'] ))
                @foreach($data['products'] as $product)

                    @php
                        if($product->name === null || $product->name === ''){
                            $product->name = $product->original_name;
                        }
                    @endphp

                    <tr>
                        <th scope="row">1</th>
                        <td>{{$product->name}}</td>
                        <td>{{$product->quantity}}</td>
                        <td>{{($product->currency_quotation * $product->currency_price)}}</td>
                        <td>{{(int)($product->currency_quotation * $product->currency_price)}} x {{$product->quantity}}</td>
                    </tr>

                    @php
                        $sum += (int)($product->currency_quotation * $product->currency_price * $product->quantity);
                    @endphp
                @endforeach
            @endif

            <tr>
                <th scope="col" colspan="4">Итого</th>
                <td scope="col"><strong>{{$sum}} <span class="small"><i class="fas fa-ruble-sign"></i></span></strong></td>
            </tr>

            </tbody>
        </table>

    </div>
</div>
