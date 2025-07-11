@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.transfer-stats-filter')
    <div class="card card-primary">
        <div class="card-header">
            <ul class="list-inline card-title">
                <li><b>Transfer Stats (All amounts in {{ empty($app['request_stack']->getCurrentRequest()->get('currency')) ? 'EUR' : $app['request_stack']->getCurrentRequest()->get('currency') }})</b></li>
            </ul>
            @if(!empty($paginator['data']) && p('accounting.section.transfer-stats.download.csv'))
                <a class="mr-2 float-right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_params) }}"><i class="fa fa-download"></i> Download</a>
                <a class="mr-2 float-right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_params, ['send_to_browse' => 1]) }}"><i class="fa fa-user-plus"></i> Send to browse users</a>
            @endif
        </div>
        <div class="card-body">
            <table id="accounting-section-datatable" class="table table-striped table-bordered dt-responsive"
                   cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Exec date</th>
                    <th>User</th>
                    <th>Method</th>
                    <th>Details</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Fee</th>
                    <th>Deducted</th>
                    <th>Status</th>
                    <th>External ID</th>
                    <th>Internal ID</th>
                    <th>Loc ID</th>
                    <th>Appr. By</th>
                </tr>
                </thead>
                <tbody>
                @foreach($paginator['data'] as $element)
                    <tr>
                        <td>{{ $element->date }}</td>
                        <td>{{ $element->exec_date }}</td>
                        <td>{{ $element->username }}</td>
                        <td>{{ $element->method }}</td>
                        <td>{{ $element->details }}</td>
                        <td>{{ $element->type }}</td>
                        <td>{{ $element->amount }}</td>
                        <td>{{ $element->fee }}</td>
                        <td>{{ $element->deducted }}</td>
                        <td>{{ $element->status }}</td>
                        <td>{{ $element->ext_id }}</td>
                        <td>{{ $element->id }}</td>
                        <td>{{ $element->loc_id }}</td>
                        <td>{{ $element->actor }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">
                Stats per provider @if(empty($app['request_stack']->getCurrentRequest()->get('currency'))) (All currencies converted in EUR) @endif
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
            @foreach($stats_total as $key => $value)
                <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-3 col-fhd-3">
                  @include('admin.accounting.partials.stats-card', ['period' => $stats_period[$key], 'total' => $value, 'title' => $key, 'currency' => $currency])
                </div>
            @endforeach
            </div>
        </div>
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
                "url" : "{{ $app['url_generator']->generate('accounting-transfer-stats', ['user' => $user->id]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#filter-form-player-balance').serializeArray();
                }
            };
            table_init['columns'] = [
                { "data": "date" },
                { "data": "exec_date" },
                { "data": "username" },
                { "data": "method" },
                { "data": "details" },
                { "data": "type" },
                { "data": "amount" },
                { "data": "fee" },
                { "data": "deducted" },
                { "data": "status" },
                { "data": "ext_id" },
                { "data": "id" },
                { "data": "loc_id" },
                { "data": "actor" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 0, 'desc' ] ];
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            /*table_init['responsive'] = { "details" : { "type": 'column' } };*/
            table_init['deferLoading'] = parseInt("{{ $paginator['recordsTotal'] }}");
            table_init['pageLength'] = 25;

            var table = $("#accounting-section-datatable").DataTable(table_init);

            table.DataTable({
                "drawCallback": function( settings ) {
                    $("#accounting-section-datatable").wrap( "<div class='table-responsive'></div>" );
                }
            });

        });
    </script>
@endsection
