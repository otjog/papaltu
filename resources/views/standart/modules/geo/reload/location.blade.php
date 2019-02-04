@if(isset($global_data['project_data']['geo']['city_name']) && $global_data['project_data']['geo']['city_name'] !== null)
    <h3>Способы доставки в
        <a href="#" data-toggle="modal" data-target="#change-city-form">
            {{$global_data['project_data']['geo']['city_name']}}
        </a>
    </h3>
@else
    <div>Выберите
        <a href="#" data-toggle="modal" data-target="#change-city-form">
            город доставки
        </a>
    </div>
@endif