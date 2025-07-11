@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.bonus.partials.bonuses-filter')
    <div class="card">
        @include('admin.user.bonus.partials.nav-bonuses')
        <div class="card-body">
            <div class="row">
                <div class="table-responsive">
                    <table id="user-datatable" class="table table-striped table-bordered dt-responsive"
                           cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>Bonus Type</th>
                            <th>Bonus</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Transaction Time</th>
                            <th>Bonus Activation</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Bonus Status</th>
                            <th>Wager Req.</th>
                            <th>Bonus Progress</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rewards_transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->bonus_type }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::getBonusName($transaction->bonus, $user->repo->getCurrencyObject()) }}
                                    @if($transaction->comment)
                                        <i class="fa fa-commenting" data-toggle="tooltip" data-placement="right"
                                           title="{{ $transaction->comment }}"></i>
                                    @endif
                                </td>
                                <td>{{ !is_null($transaction->bonus_amount) ? \App\Helpers\DataFormatHelper::nf($transaction->bonus_amount) : '' }}</td>
                                <td>{{ $transaction->currency }}</td>
                                <td>{{ $transaction->transaction_time }}</td>
                                <td>{{ $transaction->activation_time }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::getCashTransactionsTypeName($transaction->transaction_type) }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td>{{ $transaction->bonus_status }}</td>
                                <td>{{ !empty($transaction->be_id) ? \App\Helpers\DataFormatHelper::nf($transaction->wager_req) : '' }}</td>
                                <td>@if(!empty($transaction->be_id) && $transaction->bonus_status != 'failed') @if($transaction->progress == 0) 100 @else {{ round($transaction->progress,2) }} @endif % @endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "columnDefs": [{"targets": 0, "orderable": false, "searchable": false}]
            });
        });
    </script>
@endsection
