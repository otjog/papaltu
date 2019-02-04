@if(isset($deliveryServices) && count($deliveryServices) > 0 && $deliveryServices !== null)

    <div class="geo_change_location">
        @include($global_data['project_data']['template_name'] .'.modules.geo.reload.location')
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
            <div class="reload col">
                @include( $global_data['project_data']['template_name'] .'.modules.delivery.reload.offers')
            </div>
        </div>
    @endforeach
@endif
