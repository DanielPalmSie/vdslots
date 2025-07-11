<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @foreach($app['vs.menu']['user.profile']['submenu'] as $submenu_element)
            @if(p($submenu_element['permission']))
                <li class="breadcrumb-item">
                    <a href="{{ $app['url_generator']->generate($submenu_element['url']) }}">
                        <i class="fas fa-chevron-circle-right"></i>
                        {{ $submenu_element['name'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
