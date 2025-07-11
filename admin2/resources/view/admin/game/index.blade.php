@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Games Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('game.dashboard') }}">Games</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.game.partials.topmenu')

        <div class="row">
            <div class="col-lg-6 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-bonus"></i>Game Overrides</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <h5>Configure Game Overrides.</h5>
                        <a href="{{ $app['url_generator']->generate('games-override') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Game Overrides</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-gamepad"></i> Games</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <h5>Games is a games to play.</h5>
                        <a href="{{ $app['url_generator']->generate('settings.games.edit') }}"><i class="fa fa-plus"></i>
                            Add New Game</a> |
                        <a href="{{ $app['url_generator']->generate('games.bulk-import') }}"><i class="fa fa-download"></i>
                        Import Games in Bulk </a> |
                        <a href="{{ $app['url_generator']->generate('settings.games.index') }}"><i class="fa fa-list"></i>
                            List and <i class="fa fa-search"></i> Search Games</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-gamepad"></i> Game Operators</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <p>Here you can add modify operators and it's templates to add and customize game settings.</p>
                        <a href="{{ $app['url_generator']->generate('settings.operators.edit') }}"><i
                                class="fa fa-plus"></i> Add New Operator</a> or
                        <a href="{{ $app['url_generator']->generate('settings.operators.index') }}"><i
                                class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Operators</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
