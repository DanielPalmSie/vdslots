@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.player-balance-filter')
    <div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">Player Balance Report</h3>
            @if(!empty($paginator['data']) && p('accounting.section.player-balance.download.csv'))
                <a class="mr-2 float-right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_params) }}"><i class="fa fa-download"></i> Download</a>
            @endif
        </div><!-- /.card-header -->
        <div class="card-body">
            <table id="accounting-section-datatable" class="table table-striped table-bordered dt-responsive w-100 border-collapse">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Country</th>
                    <th>Currency</th>
                    <th>Cash Balance</th>
                    <th>Bonus Balance</th>
                    <th>Extra Balance <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="right" title="Ex. New Booster Vault"></i></th>
                </tr>
                </thead>
                <tbody>
                @foreach($paginator['data'] as $element)
                    <tr>
                        <td>{{ $element->user_id }}</td>
                        <td>{{ $element->country }}</td>
                        <td>{{ $element->currency }}</td>
                        <td>{{ $element->cash_balance }}</td>
                        <td>{{ $element->bonus_balance }}</td>
                        <td>{{ $element->extra_balance }}</td>
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
            //todo put a link in user name look at user search table
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('accounting-player-balance', ['user' => $user->id]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#filter-form-player-balance').serializeArray();
                }
            };
            table_init['columns'] = [
                { "data": "user_id" },
                { "data": "country" },
                { "data": "currency" },
                {
                    "data": "cash_balance",
                    "render": function ( data ) {
                        data = parseFloat(data);
                        return !isNaN(data) ? (data / 100).toFixed(2) : '-';
                    }
                },
                {
                    "data": "bonus_balance",
                    "render": function ( data ) {
                        data = parseFloat(data);
                        return !isNaN(data) ? (data / 100).toFixed(2) : '-';
                    }
                },
                {
                    "data": "extra_balance",
                    "render": function ( data ) {
                        data = parseFloat(data);
                        return !isNaN(data) ? (data / 100).toFixed(2) : '-';
                    }
                }
            ];
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['searching'] = false;
            table_init['order'] = [ [ 4, 'asc' ] ];
            // table_init['responsive'] = { "details" : { "type": 'column' } };
            table_init['deferLoading'] = parseInt("{{ $paginator['recordsTotal'] }}");
            table_init['pageLength'] = 25;

            var table = $("#accounting-section-datatable").DataTable(table_init);

        });
    </script>
@endsection
