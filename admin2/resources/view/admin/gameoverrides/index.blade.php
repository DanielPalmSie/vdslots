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
                    <li class="breadcrumb-item active">Game Overrides</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.game.partials.topmenu')

        <form method="post" action="{{ $app['url_generator']->generate('list-games-for-game-overrides') }}">
            <input type="hidden" name="token" value="{{$_SESSION['token']}}">

            <div class="card card-solid card-primary">
                <div class="card-header with-border">
                    <h3 class="card-title">Search for Games to Override</h3>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label" for="name">
                            Part of game name or external id / game name:
                        </label>
                        <div class="col-sm-3">
                            <input name="partial" class="form-control" type="text" value="">
                        </div>
                        <div class="col-sm-7">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @include('admin.gameoverrides.partials.list')
    </div>
@endsection
