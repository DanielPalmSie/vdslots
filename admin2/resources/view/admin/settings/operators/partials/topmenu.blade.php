
@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Games Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('settings-dashboard') }}">Settings</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('settings.operators.index') }}">Operators</a></li>
                    @if(!empty($breadcrumb_elms) && is_array($breadcrumb_elms))
                        @foreach($breadcrumb_elms as $url => $name)
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $name }}</a></li>
                        @endforeach
                    @endif
                    <li class="breadcrumb-item active">{{ $breadcrumb }}</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@include('admin.settings.partials.topmenu')
