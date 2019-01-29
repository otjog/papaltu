@php

    $template_name = $global_data['project_data']['template_name'];
    $components = $global_data['project_data']['components'];
    $info = $global_data['project_data']['info'];

@endphp

@include( $template_name . '.index' )