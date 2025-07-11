<ol class="breadcrumb">
        @foreach($app['vs.menu']['rg']['submenu'] as $submenu_element)
                @if(p($submenu_element['permission']))
                    @if($submenu_element['visible'])
                        <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate($submenu_element['url']) }}"><i class="fa fa-chevron-circle-right"></i> {{ $submenu_element['name'] }}</a></li>
                    @endif
                @endif
        @endforeach
</ol>
