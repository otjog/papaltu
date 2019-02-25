<div class="blur">
    <h4>Доставка</h4>
    @if( isset($delivery['_bestOffer']) && $delivery['_bestOffer'] !== null )
        @if( isset($template['com']) )
            @include(
                $template_name .'.components.' .
                $template['com']['section'] . '.' .
                $template['com']['component'] .
                '.modules.' .
                $template['mod']['module'] . '.' .
                $template['mod']['viewReload'])
        @else

            <div class="best-offer">
                <h5>Оптимальный вариант для этого товара:</h5>

                {{ $delivery['_bestOffer']['days'] }} дней | {{ $delivery['_bestOffer']['price'] }} {{$components['shop']['currency']['symbol']}}
                {{-- в ссылке прописать роут для перехода в случае если не работает js --}}
            </div>

        @endif

    @endif
</div>