<?php
/**
 * @var $repo \App\Repositories\FraudRepository
 */
?>

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
                    <li class="breadcrumb-item active" aria-current="page">High Depositors List</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    @include('admin.fraud.partials.highdepositfilter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">High Depositors List</h3>
            @if(p($permission))
                <a class="float-right" id="highdeps-download-link" href="#"><i class="fa fa-download"></i> Download</a>
            @endif
        </div><!-- /.card-header -->
        <div class="card-body">
            <table class="fraud-section-datatable table table-striped table-bordered dt-responsive border-left w-100 border-collapse"
                   id="fraud-high-depositors-datatable">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Verified</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Payment Method</th>
                    <th>Scheme</th>
                    <th>Transaction Details</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Internal Transaction Id</th>
                    <th>Timestamp</th>
                    <th>External Transaction Id</th>
                    <th>Recorded IP</th>
                </tr>
                </thead>
                <tbody>
                @foreach($page['data'] as $element)
                    <tr>
                        <td class="align-middle">{{ $element->user_id }}</td>
                        <td>{{ $element->verified }}</td>
                        <td>{{ $element->country }}</td>
                        <td>{{ $element->status }}</td>
                        <td>{{ $element->dep_type }}</td>
                        <td>{{ $element->scheme }}</td>
                        <td>{{ $element->card_hash }}</td>
                        <td>{{ $element->amount }}</td>
                        <td>{{ $element->currency }}</td>
                        <td>{{ $element->id }}</td>
                        <td>{{ $element->timestamp }}</td>
                        <td>{{ $element->ext_id }}</td>
                        <td>{{ $element->ip_num }}</td>
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
                "url" : "{{ $app['url_generator']->generate('fraud-high-deposits') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#fraud-high-deposits-filter').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            table_init['columns'] = [
                {
                    "data": "user_id",
                    "render": function ( data ) {
                        return '<a target="_blank" href="' + username_url + data + '/">' + data + '</a>';
                    }
                },
                {
                    "data": "verified",
                    "render": function ( data ) {
                        if (data == '1') {
                            return 'Yes';
                        } else {
                            return 'No';
                        }
                    }
                },
                { "data": "country" },
                { "data": "status" },
                { "data": "dep_type" },
                { "data": "scheme" },
                { "data": "card_hash" },
                { "data": "amount" },
                { "data": "currency" },
                { "data": "id" },
                { "data": "timestamp" },
                { "data": "ext_id" },
                { "data": "ip_num" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 0, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#fraud-high-depositors-datatable").wrap( "<div class='table-responsive'></div>" );
            };

            var table = $("#fraud-high-depositors-datatable").DataTable(table_init);

            $('#highdeps-download-link').on( 'click', function (e) {
                e.preventDefault();
                var form = $('#fraud-high-deposits-filter');
                form.append('<input type="hidden" name="export" value="1" /> ');
                form.submit();
            });
        });
    </script>
@endsection
