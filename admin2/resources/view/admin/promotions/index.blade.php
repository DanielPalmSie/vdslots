@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Promotions Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('promotions.dashboard') }}">Promotions</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')

    @include('admin.promotions.partials.topmenu')

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-solid card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-car"></i> Races</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>Casino Races Management.</p>
                    <a href="{{ $app['url_generator']->generate('promotions.races.edit') }}"><i class="fa fa-plus"></i> Add New Race</a> or
                    <a href="{{ $app['url_generator']->generate('promotions.races.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Races</a>.
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-solid card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-bonus"></i> Bonus Types</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>Bonus Types.</p>
                    <a href="{{ $app['url_generator']->generate('bonustypes.wizard') }}"><i class="fa fa-magic"></i> Bonus Type Wizard</a>,
                    <a href="{{ $app['url_generator']->generate('bonustypes.index') }}"><i class="fa fa-plus"></i> Select and Create New Bonus Type</a> or
                    <a href="{{ $app['url_generator']->generate('bonustypes.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Bonus Types</a>.
                </div>
            </div>
        </div>
    </div>

@endsection
