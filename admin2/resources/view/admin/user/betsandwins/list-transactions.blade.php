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
            <li><a>Wins</a></li>
            <li class="active"><a>Other transactions</a></li>
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
                        <th>Description</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Transaction Type</th>
                        <th>Transaction ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($transactions->count() > 0)
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->timestamp }}</td>
                            <td>{{ $transaction->description }}</td>
                            <td>{{ $transaction->currency }}</td>
                            <td>{{ $transaction->amount / 100 }}</td>
                            <td>{{ $transaction->balance / 100 }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::getCashTransactionsTypeName($transaction->transactiontype) }}</td>
                            <td>{{ $transaction->id }}</td>
                        </tr>
                    @endforeach
                    @else
                        <tr><td colspan="9"><p>No results found.</p></td></tr>
                    @endif
                    </tbody>
                </table>
                <div>{!! $transactions->render() !!}</div>
            </div><!-- /.tab-pane -->
        </div><!-- /.tab-content -->
    </div><!-- nav-tabs-custom -->
@endsection

