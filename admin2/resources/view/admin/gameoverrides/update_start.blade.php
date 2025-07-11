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
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('game.dashboard') }}"><i class="fa fa-cog mr-2"></i>Games</a></li>
                    <li class="breadcrumb-item active">Game Overrides</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.game.partials.topmenu')

        <form method="post" action="{{ $app['url_generator']->generate('games-update-override') }}">
            @include('admin.gameoverrides.partials.gameoverride_form')
        </form>
    </div>
@endsection
