@if(isset($deliveryServices) && count($deliveryServices) > 0 && $deliveryServices !== null)

    <div class="geo_change_location">
        @include($global_data['project_data']['template_name'] .'.modules.geo.product')
    </div>

    <div class="row p-2 border-bottom">
        <div class="col-3 border-right">
            Компания
        </div>
        <div class="col">
            <div class="row">

                <div class="col-6 border-right">
                    Доставка до терминала
                </div>

                <div class="col-6">
                    Доставка до дверей
                </div>

            </div>
        </div>
    </div>

    @foreach($deliveryServices as $service)
        <div data-delivery-service-alias="{{$service->alias}}" class="row p-2 border-bottom">
            <div class="col-3 border-right">
                <img src="{{ '/storage/img/elements/delivery/' . $service->alias . '/' . $service->alias .'_logo.jpg' }}" class="img-fluid">
            </div>

            <div class="row">
                <div class="col-6 border-right">
                    <div
                            class="reload col"
                            data-delivery-service-alias="{{$service->alias}}"
                            data-delivery-service-destination="toTerminal">
                        @include( $global_data['project_data']['template_name'] .'.modules.delivery.reload.offer')
                    </div>
                </div>
                <div class="col-6 border-right">
                    <div
                            class="reload col"
                            data-delivery-service-alias="{{$service->alias}}"
                            data-delivery-service-destination="toDoor">
                        @include( $global_data['project_data']['template_name'] .'.modules.delivery.reload.offer')
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
