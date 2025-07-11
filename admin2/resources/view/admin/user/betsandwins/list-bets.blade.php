@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.betsandwins.partials.filter')
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a>Bets</a></li>
            <li><a>Wins</a></li>
            <li><a>Other transactions</a></li>
            <li class="pull-right">@include('admin.user.betsandwins.partials.download-button')</li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active">
                <table id="user-transactions-datatable"
                       class="table table-responsive table-striped table-bordered dt-responsive" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Game</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Boosted RTP</th>
                        <th>Bonus Bet</th>
                        <th>ID</th>
                        <th>Transaction ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($bets->count() > 0)
                    @foreach($bets as $bet)
                        <tr>
                            <td>{{ $bet->created_at }}</td>
                            <td>{{ $bet->game_name }}</td>
                            <td>{{ $bet->currency }}</td>
                            <td>{{ $bet->amount / 100 }}</td>
                            <td>{{ $bet->balance / 100 }}</td>
                            <td>{{ $bet->loyalty / 100 }}</td>
                            <td>{{ ($bet->bonus_bet) ? 'Yes' : 'No' }}</td>
                            <td>{{ $bet->mg_id }}</td>
                            <td>{{ $bet->trans_id }}</td>
                        </tr>
                    @endforeach
                    @else
                        <tr><td colspan="9"><p>No results found.</p></td></tr>
                    @endif
                    </tbody>
                </table>
                <div>{{ $bets->render() }}</div>
            </div><!-- /.tab-pane -->
        </div><!-- /.tab-content -->
    </div><!-- nav-tabs-custom -->
@endsection

