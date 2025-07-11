@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <div class="card">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-sessions-historical', ['user' => $user->id]) }}">Historical User Sessions</a>
                <li>
            </ul>
        </div>
    </div>
@endsection
