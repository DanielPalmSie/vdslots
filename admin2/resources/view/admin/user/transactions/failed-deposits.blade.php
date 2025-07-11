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
                        <th>Date</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Transaction Details</th>
                        <th>Internal ID</th>
                        <th>External ID</th>
                        <th>Error Reason</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($deposits['data'] as $deposit)
                        <tr>
                            <td>{{ $deposit['created_at'] }}</td>
                            <td>{{ $deposit['status'] }}</td>
                            <td>{{ $deposit['supplier'] }}</td>
                            <td>
                                @if(isset($deposit['card']))
                                    {{ $deposit['card']['card_num'] }} |
                                    Type: {{ $deposit['card']['card_class'] }} |
                                    Brand: {{ $deposit['card']['brand_name'] }} |
                                    Issuer: {{ $deposit['card']['issuer_name'] }}
                                @endif
                            </td>
                            <td>{{ $deposit['id'] }}</td>
                            <td>{{ $deposit['reference_id'] }}</td>
                            <td>{{ implode(',', $deposit['reasons']) }}</td>
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
            var table_init = {};
            var page_number = 0;
            var page_length = 10;
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-transactions-failed-deposit', ['user' => $user->id]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#search-form-transactions').serializeArray();
                    d.page = page_number;
                }
            };
            table_init['columns'] = [
                { "data": "created_at" },
                { "data": "status" },
                { "data": "supplier" },
                {
                    data: "card",
                    orderable: false,
                    render: function(data) {
                        return data && data.card_num ?
                            data.card_num +
                            ' | Type: ' + data.card_class +
                            ' | Brand: ' + data.brand_name +
                            ' | Issuer: ' + data.issuer_name :
                            data;
                    }
                },
                { "data": "id" },
                { "data": "reference_id" },
                { "data": "reasons" },
            ];
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['searching'] = false;
            table_init['order'] = [ [ 1, 'desc' ] ];
            table_init['responsive'] = { "details" : { "type": 'column' } };
            table_init['deferLoading'] = parseInt("{{ $deposits['recordsTotal'] }}");
            table_init['pageLength'] = page_length;

            var table = $("#user-transactions-datatable").DataTable(table_init);

            $('#user-transactions-datatable').on( 'page.dt', function () {
                info = table.page.info();
                page_number = info.page;
            } );

        });



    </script>

@endsection
