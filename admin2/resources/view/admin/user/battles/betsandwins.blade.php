@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.partials.date-filter')
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li><a href="{{ $app['url_generator']->generate('admin.user-battles', ['user' => $user->id]) }}">Battles</a></li>
            <li><a href="{{ $app['url_generator']->generate('admin.user-battle-result', ['user' => $user->id, 't_id' => $app['request_stack']->getCurrentRequest()->get('t_id')]) }}">Battle [ID #{{ $app['request_stack']->getCurrentRequest()->get('t_id') }}] Results</a></li>
            <li class="active dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                    Battle [ID #{{ $app['request_stack']->getCurrentRequest()->get('t_id') }}] Bets and wins - Display options <span class="caret"></span>
                </a>
                <ul class="dropdown-menu pull-right">
                    <li role="presentation"><a class="mp_bw_filter_btn" data-filter="Bet" role="menuitem" tabindex="-1" href="#">Only bets</a></li>
                    <li role="presentation"><a class="mp_bw_filter_btn" data-filter="Win" role="menuitem" tabindex="-1" href="#">Only wins</a></li>
                    <li role="presentation" class="divider"></li>
                    <li role="presentation"><a class="mp_bw_filter_btn" data-filter="all" role="menuitem" tabindex="-1" href="#">Show all</a></li>
                </ul>
            </li>
        </ul>
        <input type="hidden" id="mp_bw_filter_field" name="filter-result" value="all">
        <div class="tab-content">
            <div class="tab-pane active">
                <table id="user-datatable" class="table table-responsive table-striped table-bordered dt-responsive">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Currency</th>
                        <th>Game</th>
                        <th>Bonus Bet</th>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Trans type</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($bets_and_wins as $bet_or_win)
                        <tr>
                            <td>{{ $bet_or_win->type }}</td>
                            <td>{{ $bet_or_win->created_at }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($bet_or_win->amount) }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($bet_or_win->balance) }}</td>
                            <td>{{ $bet_or_win->currency }}</td>
                            <td>{{ $bet_or_win->game_name }}</td>
                            <td>{{ ($bet_or_win->bonus_bet) ? 'Yes' : 'No' }}</td>
                            <td>{{ $bet_or_win->mg_id }}</td>
                            <td>{{ $bet_or_win->trans_id }}</td>
                            <td>{{ empty($bet_or_win->award_type) ? null : \App\Helpers\DataFormatHelper::getWinType($bet_or_win->award_type) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div><!-- /.tab-pane -->
        </div><!-- /.tab-content -->
    </div><!-- nav-tabs-custom -->
@endsection

@section('header-css')
    @parent
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.2.2/css/buttons.dataTables.min.css">
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" language="javascript" src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script type="text/javascript" language="javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script type="text/javascript" language="javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script type="text/javascript" language="javascript" src="//cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
    <script>
        $(function () {
            var table = $("#user-datatable").DataTable({
                "pageLength": 50,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "dom": 'Bfrtip',
                "buttons": [
                    {
                        extend: 'excelHtml5',
                        title: 'Battle summary'
                    },
                    {
                        extend: 'csvHtml5',
                        title: 'Battle summary'
                    },
                    {
                        extend: 'pdfHtml5',
                        title: 'Battle summary'
                    }
                ]
            });

            $('.mp_bw_filter_btn').click(function(e) {
                e.preventDefault();
                $('#mp_bw_filter_field').val($(this).data('filter'));
                table.draw();
            });
        });

        $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    var filter = $('#mp_bw_filter_field').val();
                    if( filter == 'all') {
                        return true;
                    }
                    var type = data[0];
                    if (type == filter) {
                        return true;
                    }
                    return false;
                }
        );
    </script>
@endsection