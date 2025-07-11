@if(!empty($transactions) && count($transactions) > 0)
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                Other transactions
            </h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-transactions-datatable"
                       class="table table-striped table-bordered dt-responsive" cellspacing="0"
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
