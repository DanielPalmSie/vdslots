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
            <li><a>Bets</a></li>
            <li class="active"><a>Wins</a></li>
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
                        <th>Bonus Bet</th>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Award Type</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($wins->count() > 0)
                    @foreach($wins as $win)
                        <tr>
                            <td>{{ $win->created_at }}</td>
                            <td>{{ $win->game_name }}</td>
                            <td>{{ $win->currency }}</td>
                            <td>{{ $win->amount / 100 }}</td>
                            <td>{{ $win->balance / 100 }}</td>
                            <td>{{ ($win->bonus_bet) ? 'Yes' : 'No' }}</td>
                            <td>{{ $win->mg_id }}</td>
                            <td>{{ $win->trans_id }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::getWinType($win->award_type) }}</td>
                        </tr>
                    @endforeach
                    @else
                        <tr><td colspan="9"><p>No results found.</p></td></tr>
                    @endif
                    </tbody>
                </table>
                <div>{!! $wins->render() !!}</div>
            </div><!-- /.tab-pane -->
        </div><!-- /.tab-content -->
    </div><!-- nav-tabs-custom -->
@endsection

