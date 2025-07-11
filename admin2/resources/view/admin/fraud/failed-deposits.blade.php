@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    Fraud Section
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fraud</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Failed Deposits</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    <form id="fraud-failed-form" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-info">Search</button>
            </div>
        </div>
    </form>
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Failed Deposits</h3>
            @include('admin.fraud.partials.download-button')
        </div>
        <div class="card-body">
            <table id="fraud-failed-datatable" class="table table-striped table-bordered dt-responsive border-left w-100 border-collapse">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Country</th>
                    <th>Verified</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th>Internal ID</th>
                    <th>External ID</th>
                    <th>Reason</th>
                </tr>
                </thead>
                <tbody>
                @foreach($deposits['data'] as $deposit)
                    <tr>
                        <td>{{ $deposit['created_at'] }}</td>
                        <td>{{ $deposit['user_id'] }}</td>
                        <td>{{ $deposit['username'] }}</td>
                        <td>{{ $deposit['country'] }}</td>
                        <td>{{ $deposit['verified'] }}</td>
                        <td>{{ $deposit['status'] }}</td>
                        <td>{{ $deposit['supplier'] }}</td>
                        <td>{{ $deposit['id'] }}</td>
                        <td>{{ $deposit['reference_id'] }}</td>
                        <td>{{ implode(', ', $deposit['reasons']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div><!-- /.card-body -->
    </div>

@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            //todo put a link in username look at user search table
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('fraud-failed-deposits', ['user' => htmlspecialchars($user->username)]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#fraud-failed-form').serializeArray();
                },
                "error": function(jqXHR, textStatus, errorThrown) {
                    alert("MTS service error (" + errorThrown +")");
                }
            };
            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            table_init['columns'] = [
                { "data": "created_at" },
                { "data": "user_id" },
                {
                    "data": "username",
                    "render": function ( data ) {
                        return '<a target="_blank" href="' + username_url + data + '/">' + data + '</a>';
                    },
                    orderable: false
                },
                {
                    "data": "country",
                    orderable: false
                },
                {
                    "data": "verified",
                    orderable: false
                },
                { "data": "status"  },
                { "data": "supplier" },
                { "data": "id" },
                { "data": "reference_id" },
                {
                    "data": "reasons",
                    orderable: false
                }
            ];
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['searching'] = false;
            table_init['order'] = [ [ 0, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $deposits['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $(this).wrap( "<div class='table-responsive'></div>" );
            };

            var table = $("#fraud-failed-datatable").DataTable(table_init);

        });
    </script>
@endsection
