@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.accounting.partials.topmenu')

        <form id="jackpot-log-filter" action="{{ $app['url_generator']->generate('accounting-jackpot-log') }}" method="get">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title">Filters</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                        <div class="form-group col-6 col-md-4 col-lg-2">
                            <label for="select-jackpot-name">Jackpot Name</label>
                            <select name="jackpot_name" id="select-jackpot-name" class="form-control select2-class"
                                    style="width: 100%;" data-placeholder="Select a jackpot" data-allow-clear="true">
                                <option value="all">All</option>
                                @foreach($jp_names as $jp_name)
                                    <option value="{{ $jp_name }}">{{ $jp_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-4 col-lg-2">
                            <label for="select-game-name">Game Name</label>
                            <select name="game_name" id="select-game-name" class="form-control select2-class"
                                    style="width: 100%;" data-placeholder="Select a game" data-allow-clear="true">
                                <option value="all">All</option>
                                @foreach($game_names as $game_name)
                                    <option value="{{ $game_name }}">{{ $game_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-4 col-lg-2">
                            <label for="select-currency">Currency</label>
                            <select name="currency" id="select-currency" class="form-control select2-class"
                                    style="width: 100%;" data-placeholder="Select a currency" data-allow-clear="true">
                                <option value="all">All</option>
                                @foreach(\App\Helpers\DataFormatHelper::getCurrencyList() as $currency)
                                    <option value="{{ $currency->code }}">{{ $currency->symbol }} {{ $currency->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6 col-md-4 col-lg-2">
                            <label for="network">Network</label>
                            <select name="network" id="select-game-network" class="form-control select2-class"
                                    style="width: 100%;" data-placeholder="Select network" data-allow-clear="true">
                                <option value="all">All</option>
                                @foreach($networks as $network)
                                    <option value="{{ $network }}">{{ ucfirst($network) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info">Search</button>
    {{--                <button class="btn btn-warning" id="export-report" type="submit" name="export" value="1">Export to csv</button>--}}
                </div>
            </div>
        </form>

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Jackpot Log</h3>
            </div><!-- /.card-header -->
            <div class="card-body">
                <table class="table table-striped table-bordered dt-responsive"
                    id="jackpot-log-datatable" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Jackpot name</th>
                        <th>Jackpot ID</th>
                        <th>Game name</th>
                        <th>Network</th>
                        <th>Currency</th>
                        <th>Current value</th>
                        <th>Contributions</th>
                        <th>Payout triggered amount</th>
                        <th>Type</th>
                        <th>Configuration</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($page['data'] as $element)
                        <tr>
                            <td>{{ $element->created_at }}</td>
                            <td>{{ $element->jp_name }}</td>
                            <td>{{ $element->jp_id }}</td>
                            <td>{{ $element->game_name }}</td>
                            <td>{{ $element->network }}</td>
                            <td>{{ $element->currency }}</td>
                            <td>{{ $element->jp_value }}</td>
                            <td>{{ $element->contributions }}</td>
                            <td>{{ $element->trigger_amount }}</td>
                            <td>{{ $element->local == 1 ? 'Local' : 'Global' }}</td>
                            <td>{{ $element->configuration }}</td>
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
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-jackpot-name").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('jackpot_name', 'all') }}").change();
            $("#select-game-name").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('game_name', 'all') }}").change();
            $("#select-currency").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('currency', 'all') }}").change();
            $("#select-game-network").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('network', 'all') }}").change();
        });
    </script>
    <script>
        $(function () {
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('accounting-jackpot-log') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#jackpot-log-filter').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['columns'] = [
                { "data": "created_at" },
                { "data": "jp_name" },
                { "data": "jp_id" },
                { "data": "game_name" },
                { "data": "network" },
                { "data": "currency" },
                { "data": "jp_value" },
                { "data": "contributions" },
                { "data": "trigger_amount" },
                {
                    "data": "local",
                    "render": function ( data ) {
                        return data == 1 ? 'Local' : 'Global';
                    }
                },
                { "data": "configuration" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 3, 'asc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#jackpot-log-datatable").wrap( "<div class='table-responsive'></div>" );
            };
            var table = $("#jackpot-log-datatable").DataTable(table_init);
        });
    </script>
@endsection
