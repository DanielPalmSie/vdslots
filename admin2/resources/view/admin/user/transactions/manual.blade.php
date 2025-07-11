@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.transactions.partials.filter')
    <div class="card">
        @include('admin.user.transactions.partials.nav-transactions')

        <div class="card-body">
            <table id="user-transactions-datatable" class="table table-striped table-bordered dt-responsive nowrap"
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
                    <th>Recorded IP</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                @foreach($manual_deposits as $manual_deposit)
                    <tr>
                        <td>{{ $manual_deposit->actor }}</td>
                        <td>{{ $manual_deposit->deposit->timestamp }}</td>
                        <td>{{ $manual_deposit->deposit->status }}</td>
                        <td>{{ $manual_deposit->deposit->dep_type }}</td>
                        <td>{{ $repo->getDepositDetails($manual_deposit->deposit) }}</td>
                        <td>{{ $manual_deposit->deposit->amount / 100 }}</td>
                        <td>{{ $manual_deposit->deposit->id }}</td>
                        <td>{{ $manual_deposit->deposit->ext_id }}</td>
                        <td>{{ $manual_deposit->deposit->ip_num }}</td>
                        <td>{{ $manual_deposit->descr }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
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
