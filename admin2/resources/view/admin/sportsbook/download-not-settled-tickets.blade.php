@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Sportsbook - Download not settled tickets</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-gear"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('sportsbook.index') }}">Sportsbook</a></li>
                    <li class="breadcrumb-item active">Download not settled tickets</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.sportsbook.partials.topmenu')
    <div class="container-fluid">
        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Download not settled tickets</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <a class="btn btn-info" href="{{$app['sportsbook']['user-service-sport-url']}}/sports/not-settled-tickets">Download</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

