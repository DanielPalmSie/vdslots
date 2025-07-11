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

        <form method="post" action="{{ $app['url_generator']->generate('games-create-override') }}">
            @include('admin.gameoverrides.partials.gameoverride_form')
        </form>

        <br/>
        <br/>
        <table class="table table-striped table-bordered">
            <tr>
                <th>Id</th>
                <th>Jurisdiction</th>
                <th>Internal game ID</th>
                <th>Ext game name / id</th>
                <th>Ext launch id / id</th>
                <th>RTP</th>
                <th>RTP Modifier</th>
                <th>Device Type</th>
                <th>Device Type Num.</th>
                <th></th>
            </tr>
            @foreach($overrides as $o)
            <tr>
                <td>{{ $o->id }}</td>
                <td>{{ $o->country }}</td>
                <td>{{ $o->game_id }}</td>
                <td>{{ $o->ext_game_id }}</td>
                <td>{{ $o->ext_launch_id }}</td>
                <td>{{ $o->payout_percent }}</td>
                <td>{{ $o->payout_extra_percent }}</td>
                <td>{{ $o->device_type }}</td>
                <td>{{ $o->device_type_num }}</td>
                <td>
                    <a href="{{ $app['url_generator']->generate('games-update-override-start') }}?id={{ $o->id }}">Update</a>
                    &nbsp;
                    &nbsp;
                    <a href="{{ $app['url_generator']->generate('games-delete-override') }}?id={{ $o->id }}&game_id={{ $o->game_id }}">Delete</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
@endsection
