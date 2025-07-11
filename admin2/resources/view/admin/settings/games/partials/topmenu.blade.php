
@section('content-header')
    @if($app['request_stack']->getCurrentRequest() && $app['request_stack']->getCurrentRequest()->query->get('arg1'))
        @php
            $menuKey =  $app['request_stack']->getCurrentRequest()->query->get('arg1');
            $urlRoute = $app['vs.menu']['games']['submenu'][$menuKey]['url'] ?? 'settings.games.index';
            $urlName = $app['vs.menu']['games']['submenu'][$menuKey]['name'] ?? 'Games';
        @endphp
    @endif
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Games Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('game.dashboard') }}">Games</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate($urlRoute) }}">{{$urlName}}</a></li>
                    @if(!empty($breadcrumb_elms) && is_array($breadcrumb_elms))
                        @foreach($breadcrumb_elms as $url => $name)
                            @if(!is_null($name))
                                <li class="breadcrumb-item"><a href="{{ $url }}">{{ $name }}</a></li>
                            @endif
                        @endforeach
                    @endif
                    <li class="breadcrumb-item active">{{ $breadcrumb }}</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@include('admin.game.partials.topmenu')
