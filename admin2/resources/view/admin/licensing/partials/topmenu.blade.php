<ol class="breadcrumb">
    @foreach($app['vs.menu']['licensing']['submenu'] as $submenu_element)
        @if(p($submenu_element['permission']))
            <li><a href="{{ $app['url_generator']->generate($submenu_element['url']) }}"><i class="fa fa-chevron-circle-right"></i> {{ $submenu_element['name'] }}</a></li>
        @endif
    @endforeach
</ol>
