@isset( $banners )
    <div id="carouselIndicators" class="carousel slide banner container" data-ride="carousel">
        <ol class="carousel-indicators">
            @for($i = 0; $i < count($banners); $i++)
                <li data-target="#carouselIndicators" data-slide-to="{{$i}}" @if($i === 0) class="active" @endif></li>
            @endfor
        </ol>
        <div class="carousel-inner">
            @foreach($banners as $banner)
                @include( $template_name .'.modules.banner.' . $banner->type . '.' . $banner->template)
            @endforeach
        </div>
        <a class="carousel-control-prev" href="#carouselIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
@endisset