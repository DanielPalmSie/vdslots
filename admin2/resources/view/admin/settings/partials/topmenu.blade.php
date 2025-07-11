<ol class="breadcrumb">
    @foreach($app['vs.menu']['settings']['submenu'] as $key => $submenu_element)
        @if(p($submenu_element['permission']))
            @if ($app['request_stack']->getCurrentRequest()->get('arg1') == $key)
                <li class="breadcrumb-item"><i class="fa fa-chevron-circle-down"></i> {{ $submenu_element['name'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate($submenu_element['url']) }}"><i class="fa fa-chevron-circle-right"></i> {{ $submenu_element['name'] }}</a></li>
            @endif
        @endif
    @endforeach
</ol>

