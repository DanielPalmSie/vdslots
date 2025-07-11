@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.transactions.partials.filter')

    <div class="card">
        @include('admin.user.transactions.partials.nav-transactions')

        <div class="card-body">
            <div class="table-responsive">
                <table id="user-transactions-datatable"
                       class="table table-striped table-bordered dt-responsive nowrap"
                       cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Transaction Type</th>
                        <th>Actor</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Transaction Details</th>
                        <th>{{ $user->currency }}</th>
                        <th>Internal ID</th>
                        <th>External ID</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cash_transactions as $cash_transaction)
                        <tr>
                            <td>{{ \App\Helpers\DataFormatHelper::getCashTransactionsTypeName($cash_transaction->transactiontype) }}</td>
                            <td>{{ is_null($cash_transaction->ip_log->actor_username) ? 'System' : $cash_transaction->ip_log->actor_username }}</td>
                            <td>{{ $cash_transaction->timestamp }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ $cash_transaction->bonus_id != 0 ? "Bonus id: {$cash_transaction->bonus_id}" : null }}</td>
                            <td>{{ $cash_transaction->amount / 100 }} {{ $cash_transaction->currency }}</td>
                            <td>{{ $cash_transaction->id }}</td>
                            <td></td>
                            <td>{{ $cash_transaction->description }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection


@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-transactions-datatable").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]]
            });
        });
    </script>

@endsection
