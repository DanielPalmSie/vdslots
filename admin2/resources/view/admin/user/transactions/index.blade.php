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
                <a href="{{ $app['url_generator']->generate('admin.user-transactions-deposit', ['user' => $user->id]) }}">Deposits</a>
            </li>
            <li><a href="{{ $app['url_generator']->generate('admin.user-transactions-failed-deposit', ['user' => $user->id]) }}">Failed Deposits</a></li>
            <li>
                <a href="{{ $app['url_generator']->generate('admin.user-transactions-manual', ['user' => $user->id]) }}">Manual Deposits</a>
            </li>
            <li>
                <a href="{{ $app['url_generator']->generate('admin.user-transactions-withdrawal', ['user' => $user->id]) }}">Withdrawals</a>
            </li>
            <li>
                <a href="{{ $app['url_generator']->generate('admin.user-transactions-other', ['user' => $user->id]) }}">Other transactions</a>
            </li>
            <li>
                <a href="{{ $app['url_generator']->generate('admin.user-transactions-closed-loop', ['user' => $user->id]) }}">Closed Loop Overview</a>
            </li>
        </ul>
    </div><!-- nav-tabs-custom -->
@endsection