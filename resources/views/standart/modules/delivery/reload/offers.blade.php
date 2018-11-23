<div class="blur">

    @if( isset( $delivery['costs'] ) && count( $delivery['costs'] ) > 0)

        @if( isset($inc_template['com']) )

            @include(
                $template_name .'.components.' .
                $inc_template['com']['section'] . '.' .
                $inc_template['com']['component'] .
                '.modules.' .
                $inc_template['mod']['module'] . '.' .
                $inc_template['mod']['viewReload'])
        @else

            <h4>Самовывоз из пунктов выдачи</h4>

            @foreach($delivery['costs'] as $company => $services)
                @if( isset( $services['toTerminal']))
                    <div class="row pb-2 pt-2 border-top ">
                        <div class="col-4 text-center"><img src="{{ '/storage/img/elements/delivery/' . $company . '/' . $company .'_logo.jpg' }}" class="img-fluid"></div>
                        <div class="col-4 text-center">{{$services['toTerminal']['price']}} {{$components['shop']['currency']['symbol']}}</div>
                        <div class="col-4 text-center">{{$services['toTerminal']['days']}} дней</div>
                    </div>
                @endif
            @endforeach

        <hr>

            <h4>Доставка до адреса</h4>

            @foreach($delivery['costs'] as $company => $services)
                @if( isset( $services['toDoor']))
                    <div class="row pb-2 pt-2 border-top ">
                        <div class="col-4 text-center"><img src="{{ '/storage/img/elements/delivery/' . $company . '/' . $company .'_logo.jpg' }}" class="img-fluid"></div>
                        <div class="col-4 text-center">{{$services['toDoor']['price']}} {{$components['shop']['currency']['symbol']}}</div>
                        <div class="col-4 text-center">{{$services['toDoor']['days']}} дней</div>
                    </div>
                @endif
            @endforeach

        @endif

    @else
        @include( $template_name .'.modules.delivery.elements.error')

        @if( isset($inc_template['com']) )
            @includeIf(
                $template_name .'.components.' .
                $inc_template['com']['section'] . '.' .
                $inc_template['com']['component'] .
                '.modules.' .
                $inc_template['mod']['module'] . '.' .
                '.errors.' .//!!!
                $inc_template['mod']['viewReload'], ['shipments' => $delivery['shipments']])
        @endif
    @endif
</div>

@include( $template_name .'.modules.delivery.elements.progress')