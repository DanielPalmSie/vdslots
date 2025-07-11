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
                    <label for="payment-method">Payment Method</label>
                    <select id="payment-method" class="form-control">
                        <option></option>
                        @foreach($deposits_data['methods_list'] as $method)
                            <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

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
                @foreach($deposits_data['paginator']['data'] as $deposit)
                    <tr>
                        <td>{{ $deposit->actor }}</td>
                        <td>{{ $deposit->timestamp }}</td>
                        <td>{{ is_numeric($deposit->status) && $deposit->status == -1 ? 'Failed' : $deposit->status }}</td>
                        <td>{{ $deposit->dep_type }}</td>
                        <td>{{ $deposit->details }}</td>
                        <td>{{ $deposit->amount }}</td>
                        <td>{{ $deposit->id }}</td>
                        <td>{{ $deposit->ext_id }}</td>
                        <td>{{ $deposit->ip_num }}</td>
                        <td>{{ $deposit->descr }}</td>
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
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-transactions-deposit', ['user' => $user->id]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#search-form-transactions').serializeArray();
                    d.dep_custom = $('#payment-method').val();
                }
            };
            table_init['columns'] = [
                { "data": "actor" },
                { "data": "timestamp" },
                { "data": "status" },
                { "data": "dep_type" },
                { "data": "details" },
                { "data": "amount" },
                { "data": "id" },
                { "data": "ext_id" },
                { "data": "ip_num" },
                { "data": "descr" }
            ];
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['searching'] = false;
            table_init['order'] = [ [ 1, 'desc' ] ];
            table_init['responsive'] = { "details" : { "type": 'column' } };
            table_init['deferLoading'] = parseInt("{{ $initial['defer_option'] }}");
            table_init['pageLength'] = parseInt("{{ $initial['initial_length'] }}");

            var table = $("#user-transactions-datatable").DataTable(table_init);

            $('#payment-method').change(function () {
                table.ajax.reload();
            });
        });
    </script>
@endsection
