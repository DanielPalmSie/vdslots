<ol class="breadcrumb">
    @if($app['request_stack']->getCurrentRequest() && $app['request_stack']->getCurrentRequest()->query->get('arg1'))
        @php
            $menuKey =  $app['request_stack']->getCurrentRequest()->query->get('arg1') == 'games' ? 'games' : 'gamification';
        @endphp
    @endif
    @foreach($app['vs.menu'][$menuKey ?? 'gamification']['submenu'] as $key => $submenu_element)
        @if(p($submenu_element['permission']))
            @if ($app['request_stack']->getCurrentRequest() && $app['request_stack']->getCurrentRequest()->query->get('arg1') == $key)
                <li class="breadcrumb-item"><i class="fa fa-chevron-circle-down"></i> {{ $submenu_element['name'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate($submenu_element['url']) }}"><i class="fa fa-chevron-circle-right"></i> {{ $submenu_element['name'] }}</a></li>
            @endif
        @endif
    @endforeach
</ol>
