@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li>
                <a href="{{ $app['url_generator']->generate('admin.user-game-sessions-historical', ['user' => $user->id]) }}">Historical
                    Game Sessions</a></li>
            <li>
                <a href="{{ $app['url_generator']->generate('admin.user-game-sessions-inprogress', ['user' => $user->id]) }}">Game
                    Sessions In Progress</a></li>
            <li>
                <a href="{{ $app['url_generator']->generate('admin.user-game-sessions-logged', ['user' => $user->id]) }}">Logged
                    Sessions</a></li>
        </ul>
    </div><!-- nav-tabs-custom -->
@endsection