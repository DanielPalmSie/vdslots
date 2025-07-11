<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @foreach($app['vs.menu']['accounting']['submenu'] as $submenu_element)
            @if (!$submenu_element['hidden'])
                @if(p($submenu_element['permission']))
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate($submenu_element['url']) }}"><i class="fa fa-chevron-circle-right"></i> {{ $submenu_element['name'] }}</a></li>
                @endif
            @endif
        @endforeach
    </ol>
</nav>
