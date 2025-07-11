@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1> Sportsbook Dashboard</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('sportsbook.index') }}">Sportsbook</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')

<div class="container-fluid">

    @include('admin.sportsbook.partials.topmenu')

    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="card card-solid card-primary">
                <div class="card-header">
                    <h3 class="card-title">Clean Events</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>It's a tool to clean up stuck events on sportsbook.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card card-solid card-primary">
                <div class="card-header">
                    <h3 class="card-title">Unsettled Bets</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>It's a tool to get tickets not settled on sportsbook.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
