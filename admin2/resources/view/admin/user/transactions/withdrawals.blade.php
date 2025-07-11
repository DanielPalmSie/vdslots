@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.transactions.partials.filter')

    <div class="card">
        @include('admin.user.transactions.partials.nav-transactions')
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3 col-lg-2">
                    <label for="payment-method" class="form-label">Payment Method</label>
                    <select id="payment-method" class="form-control">
                        <option value="" selected disabled>Select Payment Method</option>
                        @foreach($withdrawals['methods_list'] as $method)
                            <option value="{{ ucwords($method) }}">{{ ucwords($method) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>


                <table id="user-transactions-datatable"
                       class="table table-striped table-bordered"
                       cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Actor</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Transaction Details</th>
                        <th>{{ $user->currency }}</th>
                        <th>Internal ID</th>
                        <th>External ID</th>
                        <th>Ref Code</th>
                        <th>Recorded IP</th>
                        <th>Description</th>
                        <th>Error Reason</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($withdrawals['withdrawals'] as $withdrawal)
                        <tr>
                            <td>{{ $withdrawal->approved_by != 0 ? $withdrawal->actor->username : 'System' }}</td>
                            <td>{{ $withdrawal->timestamp }}</td>
                            @if ($withdrawal->status == 'approved')
                                <td>
                                    {{ ucfirst($withdrawal->status) }} at {{ $withdrawal->approved_at }}
                                    @if (p('cancel.approved.withdrawal'))
                                        [<a id="cancel-approved-link"
                                            href="{{ $app['url_generator']->generate('admin.user-cancel-withdrawal', ['user' => $user->id, 'id' => $withdrawal->id]) }}"
                                            class="href-confirm"
                                            data-message="Are you sure you want to cancel this withdrawal?">
                                            Cancel</a>]
                                    @endif
                                </td>
                            @elseif($withdrawal->status == 'pending')
                                <td>
                                    {{ ucfirst($withdrawal->status) }}
                                    [<a id="cancel-pending-link"
                                        href="{{ $app['url_generator']->generate('admin.user-cancel-pending-withdrawal', ['user' => $user->id, 'id' => $withdrawal->id, 'action' => 'delete']) }}"
                                        class="href-confirm"
                                        data-message="Are you sure you want to cancel this pending withdrawal?">
                                        Cancel</a>]
                                    @if (empty($withdrawal->flushed) && p('flush.pending'))
                                        [<a id="flush-pending-link"
                                            href="{{ $app['url_generator']->generate('admin.user-cancel-pending-withdrawal', ['user' => $user->id, 'id' => $withdrawal->id, 'action' => 'flush']) }}"
                                            class="href-confirm"
                                            data-message="Are you sure you want to flush this pending withdrawal?">
                                            Flush</a>]
                                    @endif
                                </td>
                            @elseif(in_array($withdrawal->status, ['processing', 'preprocessing']))
                                <td>{{ ucfirst($withdrawal->status) }} since {{ $withdrawal->timestamp }}</td>
                            @else
                                <td>{{ ucfirst($withdrawal->status) }} at {{ $withdrawal->approved_at }}</td>
                            @endif
                            <td>{{ ucwords($withdrawal->payment_method) }}</td>
                            <td>
                                {{ \App\Repositories\TransactionsRepository::getWithdrawalDetails($withdrawal) }}
                                {{ $withdrawal->transaction_details['credit_card']['details'] ?? '' }}
                            </td>
                            <td>{{ $withdrawal->amount / 100 }}</td>
                            <td>{{ $withdrawal->id }}</td>
                            <td>{{ $withdrawal->ext_id }}</td>
                            <td>{{ $withdrawal->ref_code }}</td>
                            <td>{{ $withdrawal->ip_num }}</td>
                            <td>{!! explode('<a', $withdrawal->description)[0] !!}</td>
                            <td>{{ $withdrawal->transaction_details['transaction_error']['description'] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
        </div>
    </div>

    @include('admin.partials.href-confirm')
@endsection



@section('footer-javascript')
    @parent
    <script>
        $(function () {
            var table = $("#user-transactions-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[1, "desc"]],
                "responsive": true //
            });

            $('#payment-method').change(function () {
                table.draw();
            });

            $.fn.dataTable.ext.search.push(
                function (settings, data, dataIndex) {
                    var payment_method = $('#payment-method').val();
                    var method_col = data[3];

                    return (payment_method === '' || payment_method === method_col)
                }
            );
        });
    </script>
@endsection
