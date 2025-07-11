@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.transactions.partials.filter')

    <div class="card">
        @include('admin.user.transactions.partials.nav-transactions')

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 col-lg-4">
                    <form id="closed-loop-reset-form" method="post"
                          action="{{ $app['url_generator']->generate('admin.user-transactions-closed-loop', ['user' => $user->id]) }}">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                        <div class="form-group">
                            <label for="starts-at">Update the "Resets At" date for this user's closed loop ({{ $duration }} days)</label>
                            <div class="input-group">
                                <input
                                    type="datetime-local"
                                    name="ends-at"
                                    class="form-control"
                                    placeholder="Resets At"
                                    value="{{ $ends_at ? date('Y-m-d\TH:i', strtotime($ends_at)) : '' }}"
                                    required
                                >
                                @if($ends_at)
                                    <div class="input-group-append">
                                        <span class="input-group-text text-sm text-dark"><b>{{ $ends_at }}</b></span>
                                    </div>
                                @endif
                            </div>
                            <button name="closed-loop-reset-submit" class="btn btn-info mt-2" type="submit">Update</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-outline card-warning p-3">
                <strong class="black">
                    <i class="fa fa-info-circle"></i> Be advised that players are allowed to withdraw an amount equal to
                    the minimum withdrawal amount when 'Withdrawal to close loop, including Pending' amount is below
                    minimum allowed for withdrawal.
                </strong>
            </div>

            <div class="table-responsive">
                <table id="user-transactions-datatable"
                       class="table table-striped table-bordered no-footer"
                       cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Payment Method</th>
                        <th>Total Deposits ({{ $currency }})</th>
                        <th>Rerouted Total Deposits ({{ $currency }})</th>
                        <th>Total Approved Withdrawals ({{ $currency }})</th>
                        <th>Total Pending Withdrawals ({{ $currency }})</th>
                        <th>Withdrawal to Close Loop (excluding Pending {{ $currency }})</th>
                        <th>Withdrawal to Close Loop (including Pending {{ $currency }})</th>
                        <th>Starts At</th>
                        <th>Resets At ({{ $duration }} days)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($closed_loops as $psp)
                        <tr>
                            <td>{{ $psp['account_pretty'] }}</td>
                            <td>{{ $psp['deposit_amount'] / 100 }}</td>
                            <td>{{ $psp['rerouted_deposit_amount'] / 100 }}</td>
                            <td>{{ $psp['approved_withdraw_amount'] / 100 }}</td>
                            <td>{{ $psp['pending_withdraw_amount'] / 100 }}</td>
                            <td>{{ max(0, $psp['total_deposit_amount'] - $psp['approved_withdraw_amount']) / 100 }}</td>
                            <td>{{ max(0, $psp['total_deposit_amount'] - $psp['total_withdraw_amount']) / 100 }}</td>
                            <td>{{ $starts_at }}</td>
                            <td>{{ $ends_at }}</td>
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
                "pageLength": 50,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]]
            });
        });
    </script>
@endsection
