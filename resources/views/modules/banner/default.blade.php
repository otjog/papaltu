@isset( $banners )
    @foreach($banners as $banner)
        <div class="banner">
            @include('modules.banner.' . $banner->component . '.' . $banner->resource . '.default')
        </div>

    @endforeach
@endisset