@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.betsandwins.partials.filter-all')

    @if($query_data['vertical'] === 'all' || $query_data['vertical'] === 'casino')
        @include('admin.user.betsandwins.partials.bets')
        @include('admin.user.betsandwins.partials.wins')
        @include('admin.user.betsandwins.partials.betsandwins')
        @include('admin.user.betsandwins.partials.transactions')
    @endif
    @if($query_data['vertical'] === 'all' || $query_data['vertical'] === 'sportsbook')
        @include('admin.user.betsandwins.partials.sportsbook-bets')
        @include('admin.user.betsandwins.partials.altenar-bets')
    @endif
    @if($query_data['vertical'] === 'all' || $query_data['vertical'] === 'poolx')
        @include('admin.user.betsandwins.partials.poolx-bets')
    @endif
    @if($query_data['vertical'] === 'all' || $query_data['vertical'] === 'bingo')
        @include('admin.user.betsandwins.partials.bingo-bets')
    @endif
@endsection

@include('admin.user.betsandwins.partials.footer')


