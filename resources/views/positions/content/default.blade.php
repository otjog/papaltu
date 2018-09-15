
    @if(isset( $data['header_page'] ))
        @include('modules.home.default', $data)
    @endif

    @if(isset( $template['banner'] ))
        @include('modules.banner.default')
    @endif

    @if(isset( $template['component'] ))
        <div class="{{$template['resource']}}">
            <div class="container">
                <div class="row">

                    @if(isset($template['sidebar']))
                        @include('modules.'.$template['sidebar'].'.default', $data)
                    @endif

                    @include('components.'.$template['component'].'.'.$template['resource'].'.'.$template['view'], $data)

                </div>
            </div>
        </div>
    @endif

    @if(isset( $template['custom'] ) && count( $template['custom'] ) > 0)
        @foreach($template['custom'] as $module)

            @include('modules.custom.'.$module)

        @endforeach
    @endif


