@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <div class="box box-solid box-primary">
        <div class="box-header">
            Game History - Wagers
        </div>
        <div class="box-body">
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th>Date</th>
                    <th>Game Name</th>
                    <th>Wagered Amount</th>
                </tr>
                @foreach($bets as $bet)
                    <tr>
                        <td>{{ $bet->created_at }}</td>
                        <td>{{ $bet->game_name }}</td>
                        <td>{{ nfCents($bet->amount) }} {{ $bet->currency }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="box box-solid box-primary">
        <div class="box-header">
            Game History - Wins
        </div>
        <div class="box-body">
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th>Date</th>
                    <th>Game Name</th>
                    <th>Won Amount</th>
                </tr>
                @foreach($wins as $win)
                    <tr>
                        <td>{{ $win->created_at }}</td>
                        <td>{{ $win->game_name }}</td>
                        <td>{{ nfCents($win->amount) }} {{ $win->currency }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection