@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "Dashboard"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        <div class="row">
            @foreach($app['vs.menu']['rg']['submenu'] as $submenu_element)
            @if($submenu_element['visible'])
                <div class="col-lg-4 col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">{{$submenu_element['name']}}  <i class="fa {{$submenu_element['dashboard-icon']}}"></i> </h3>
                        </div>
                        <div class="card-body table-responsive">
                            <p>{{ $submenu_element['dashboard-content'] }}</p>
                            <a href="{{ $app['url_generator']->generate($submenu_element['url']) }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search {{ $submenu_element['name'] }}</a>.
                        </div>
                    </div>
                </div>
            @endif
            @endforeach
        </div>
    </div>
@endsection
