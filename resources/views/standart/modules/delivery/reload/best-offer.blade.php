<div class="blur">
    <h4>Доставка</h4>
    @if( isset($delivery['_bestOffer']) && $delivery['_bestOffer'] !== null )
        @if( isset($inc_template['com']) )
            @include(
                $global_data['project_data']['template_name'] .'.components.' .
                $inc_template['com']['section'] . '.' .
                $inc_template['com']['component'] .
                '.modules.' .
                $inc_template['mod']['module'] . '.' .
                $inc_template['mod']['viewReload'])
        @else

            <div class="best-offer">
                {{ $delivery['_geo']['city_name'] }} <a href="#" class="badge badge-info" data-toggle="modal" data-target="#change-city-form">Изменить</a>
                <h5>Оптимальный вариант для этого товара:</h5>

                {{ $delivery['_bestOffer']['days'] }} дней | {{ $delivery['_bestOffer']['price'] }} {{$global_data['project_data']['components']['shop']['currency']['symbol']}}
                {{-- в ссылке прописать роут для перехода в случае если не работает js --}}
            </div>

        @endif

    @else
        @include( $global_data['project_data']['template_name'] .'.modules.delivery.elements.error')
    @endif
</div>

@include( $global_data['project_data']['template_name'] .'.modules.delivery.elements.progress')