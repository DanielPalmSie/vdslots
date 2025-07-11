<aside class="main-sidebar sidebar-no-expand sidebar-dark-primary elevation-1">
    <a href="{{ $app['url_generator']->generate('home') }}"
       class="brand-link logo-switch d-flex justify-content-center bg-primary"
    >
        <span class="brand-text logo-xs ml-1"><b>{{ getenv('APP_TITLE_SHORT') }}</b>A</span>
        <span class="brand-text logo-xl"><b>{{ getenv('APP_TITLE_SHORT') }}</b>Admin</span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-header m-auto">
                    <p>NAVIGATION</p>
                </li>
                @foreach($app['vs.menu'] as $menu_key => $menu_element)
                    @if($app['vs.config']['active.sections'][$menu_key] && p($menu_element['permission']))
                        <li class="nav-item">
                            <a href="{{ $app['url_generator']->generate($menu_element['root.url']) }}" class="nav-link">
                                <i class="fa fa-{{ $menu_element['icon'] ? $menu_element['icon'] : 'circle-o' }} nav-icon"></i>
                                <p>{{ $menu_element['name'] }}</p>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
