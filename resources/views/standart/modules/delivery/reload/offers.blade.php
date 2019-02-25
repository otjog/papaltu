<div class="blur">

    @if( isset( $delivery['costs'] ) && count( $delivery['costs'] ) > 0)

        @if( isset($inc_template['com']) )

            @include(
                $global_data['project_data']['template_name'] .'.components.' .
                $inc_template['com']['section'] . '.' .
                $inc_template['com']['component'] .
                '.modules.' .
                $inc_template['mod']['module'] . '.' .
                $inc_template['mod']['viewReload'])

        @else

            <div class="row">

                <div class="col-6 border-right">
                    @if( isset($delivery['costs']['toTerminal']) )
                        <div class="row">
                            <div class="col text-center">{{$delivery['costs']['toTerminal']['price']}} {{$global_data['project_data']['components']['shop']['currency']['symbol']}}</div>
                            <div class="col text-center">{{$delivery['costs']['toTerminal']['days']}} дней</div>
                        </div>
                    @endif
                </div>

                <div class="col">
                    @if( isset($delivery['costs']['toDoor']) )
                        <div class="row">
                            <div class="col text-center">{{$delivery['costs']['toDoor']['price']}} {{$global_data['project_data']['components']['shop']['currency']['symbol']}}</div>
                            <div class="col text-center">{{$delivery['costs']['toDoor']['days']}} дней</div>
                        </div>
                    @endif
                </div>

            </div>

        @endif

    @else
        @include( $global_data['project_data']['template_name'] .'.modules.elements.error',
        ['msg' => 'Мы не смогли рассчитать стоимость доставки в автоматическом режиме.'])

        @if( isset($inc_template['com']) )
            @includeIf(
                $global_data['project_data']['template_name'] .'.components.' .
                $inc_template['com']['section'] . '.' .
                $inc_template['com']['component'] .
                '.modules.' .
                $inc_template['mod']['module'] . '.' .
                '.errors.' .//!!!
                $inc_template['mod']['viewReload'], ['shipments' => $delivery['shipments']])
        @endif
    @endif
</div>

@include( $global_data['project_data']['template_name'] .'.modules.elements.progress',
        ['msg' => 'Рассчитываем доставку..'])