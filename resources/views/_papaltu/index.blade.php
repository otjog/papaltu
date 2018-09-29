<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="yandex-verification" content="5cfc0ee706c042ca" />

    @if( isset($meta) && $meta !== null)
        <title> {{$meta['title']}} </title>
        <meta name="description"    content=" {{$meta['description']}} ">
        <meta name="keywords"       content=" {{$meta['keywords']}} ">
    @else
        <title>Papaltu - интернет-магазини женских пальто и курток. Утепленные пальто с доставкой по России.</title>
        <meta name="description" content="Интернет-магазин Papaltu. Каталог пальто и курток. Продажа пальто для женщин. Как купить пальто. Советы по выбору.">
        <meta name="keywords" content="Пальто демисезонные, пальто утепленные, продажа пальто, пальто с натуральным мехом, купить куртку, купить ветровку, зимние куртки, детские пальто">
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('css/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fancybox/jquery.fancybox.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/' . $template_name .'/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/' . $template_name .'/template.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/suggestions-jquery@18.6.0/dist/css/suggestions.min.css" type="text/css" />

    @if(isset($template['component']))
        @switch($template['component'])
            @case('shop')
            @case('info')
            <link rel="stylesheet" href="{{ asset('css/' . $template_name .'/' . $template['component'] . '_' . $template['resource'] . '_styles.css') }}">
            @break
            @case('search')
            <link rel="stylesheet" href="{{ asset('css/' . $template_name .'/shop_category_styles.css') }}">
            @break
        @endswitch
    @endif

    <link rel="stylesheet" href="{{ asset('css/' . $template_name .'/responsive.css') }}">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">

    @includeIf('inclusion.yandex-metrika')

</head>
<body style="margin-bottom: 0">

<div class="super_container">

    <!-- Header -->
    <header class="header">
        @include( $template_name .'.positions.header.default')
    </header>

    @include( $template_name .'.positions.content.default')

    <!-- Footer -->
    <footer class="footer" style="position: relative">
        @include( $template_name .'.positions.footer.default')
    </footer>

</div>



<script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDzy4Bx5gHQSf4kHFQMo_mFhKlfeL_3lU8">
</script>

<script src="{{ asset('js/xmlhttprequest.js') }}"></script>
<script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
<script src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/fancybox/jquery.fancybox.js') }}"></script>
<script src="{{ asset('js/fancybox/jquery.fancybox.pack.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/suggestions-jquery@18.6.0/dist/js/jquery.suggestions.min.js"></script>
<script src="{{ asset('js/dadata/forms.js')}}"></script>
<script src="{{ asset('js/ajax.js')}}"></script>
<script src="{{ asset('js/modules/delivery.js')}}"></script>
<script src="{{ asset('js/custom.js') }}"></script>

<script src="{{ asset('js/modules/product-filter.js') }}"></script>


</body>
</html>

