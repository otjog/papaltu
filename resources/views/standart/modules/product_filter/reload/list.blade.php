@if( isset( $filtered_products ) )
    @include( $global_data['project_data']['template_name'] .'.components.shop.product.list', ['products' => $filtered_products])
@else
    {{-- Progress Bar --}}
    <div class="progress" style="display: none">
        <div class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar"
             aria-valuenow="100"
             aria-valuemin="0"
             aria-valuemax="100"
             style="width: 100%">
            Ищем товары по вашим параметрам
        </div>
    </div>


    {{-- Error Block --}}
    <div class="error" style="display: none">
        Ошибка
    </div>
@endif