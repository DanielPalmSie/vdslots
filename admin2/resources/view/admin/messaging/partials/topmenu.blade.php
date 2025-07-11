@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Messaging</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate($app['vs.menu']['messaging']['root.url']) }}">Messaging</a></li>
                    <li class="breadcrumb-item active">{{ $app['vs.menu']['messaging']['submenu'][$app['request_stack']->getCurrentRequest()->query->get('arg1')]['name'] }}</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

<ol class="breadcrumb">
    @foreach($app['vs.menu']['messaging']['submenu'] as $key => $submenu_element)
        @if (!$submenu_element['hidden'])
            @if ($app['request_stack']->getCurrentRequest()->query->get('arg1') == $key)
                <li class="breadcrumb-item"><i class="fa fa-chevron-circle-down"></i> {{ $submenu_element['name'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate($submenu_element['url']) }}"><i class="fa fa-chevron-circle-right"></i> {{ $submenu_element['name'] }}</a></li>
            @endif
        @endif
    @endforeach
</ol>
