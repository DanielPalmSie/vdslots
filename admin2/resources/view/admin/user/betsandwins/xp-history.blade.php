@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <form action="#" id="xp-progress-form">
        <form method="get">
            <div class="card border-top border-top-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 col-lg-2">
                            <label for="date-range">Date range</label>
                            <input autocomplete="off" id="date-range" name="date-range" type="text" class="form-control pull-right date-range-filter"
                                   placeholder="No date range selected" value="{{ $date_range->getRange('date') }}">
                        </div>
                        <div class="col-6 col-lg-2">
                            <div class="form-group">
                                <label for="select-games">Game</label>
                                <select name="game" id="select-games" class="form-control select2-games"
                                        style="width: 100%;" data-placeholder="Select a game" data-allow-clear="true">
                                    <option></option>
                                    @foreach(\App\Repositories\GameRepository::getGameList() as $game)
                                        <option value="{{ $game->ext_game_name }}">{{ $game->game_name }} - {{ $game->device_type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button formaction="{{ $app['url_generator']->generate($form_url, ['user' => $user->id]) }}" class="btn btn-info">
                        Search
                    </button>
                </div>
            </div>
        </form>

        <div class="nav-tabs-custom">
            <div class="card card-primary border border-primary">
                <div class="card-header">
                    <p class="card-title">
                        XP History | Total XP points in period: {{$total_xp_points}}
                    </p>
                    @if (p('view.account.xp-history.download.csv'))
                        <span class="float-right">@include('admin.user.betsandwins.partials.download-button')</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="xp-progress-report-datatable"
                               class="table table-striped table-bordered dt-responsive" cellspacing="0"
                               width="100%">
                            <thead>
                            <tr>
                                <th>Bet ID</th>
                                <th>Date</th>
                                <th>Game</th>
                                <th>XP Points</th>
                                <th>ID</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div><!-- /.box -->
        </div><!-- nav-tabs-custom -->
    </form>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".select2-games").select2().val('{{ $query_data['game'] }}').change();
        });
    </script>
    <script>
        $(function () {
            var picker_elem = $('.date-range-filter');
            var picker_format = 'YYYY-MM-DD';

            picker_elem.daterangepicker(
                {
                    autoUpdateInput: false,
                    opens: "right",
                    alwaysShowCalendars: true,
                    locale: {
                        format: picker_format,
                        cancelLabel: 'Clear',
                        firstDay: 1
                    },
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 14 Days': [moment().subtract(14, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Previous Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'Last 6 Months': [moment().subtract(6, 'month'), moment()]
                    }
                }
            );
            picker_elem.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format(picker_format) + ' - ' + picker.endDate.format(picker_format));
            });
            picker_elem.on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['searching'] = false;
            table_init['pageLength'] = 25;
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['ajax'] = {
                "url": "{{ $app['url_generator']->generate('admin.user-xp-history', ['user' => $user->id]) }}",
                "type": "GET",
                "data": function (d) {
                    d.form = $('#xp-progress-form').serializeArray();
                }
            };
            table_init['columns'] = [
                {"data": "id"},
                {"data": "created_at"},
                {"data": "game_name"},
                {"data": "xp", "orderable": false},
                {"data": "mg_id"}
            ];

            var table = $("#xp-progress-report-datatable").DataTable(table_init);
            table.ajax.reload();
        });
    </script>
@endsection
