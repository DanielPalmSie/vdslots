@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.partials.date-filter')

    <div class="card">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="nav-item border-top border-primary"><a class="nav-link active">Battles</a></li>
            </ul>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active">
                        <table id="user-datatable" class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>Entry Id</th>
                                <th>Battle Id</th>
                                <th>Battle Name</th>
                                <th>Game</th>
                                <th>Battle Type</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Result Place</th>
                                <th>Won amount</th>
                                <th>Rebuy Times</th>
                                <th>Rebuy Cost</th>
                                <th>Spin Left</th>
                                <th>Total Spins</th>
                                <th>Spin Ratio</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tournament_entries as $te)
                                <tr>
                                    <td>{{ $te->entry_id }}</td>
                                    <td>{{ $te->tournament_id }}</td>
                                    <td>{{ $te->t_name }}</td>
                                    <td>{{ $te->game }}</td>
                                    <td>{{ $te->battle_type }}</td>
                                    <td>{{ $te->t_start }}</td>
                                    <td>{{ $te->t_end }}</td>
                                    <td>{{ $te->te_status }}</td>
                                    <td>{{ $te->result_place }}</td>
                                    <td>{{ $te->won_amount }}</td>
                                    <td>{{ $te->rebuy_times }}</td>
                                    <td>{{ $te->rebuy_cost }}</td>
                                    <td>{{ $te->spin_left }}</td>
                                    <td>{{ $te->total_spins }}</td>
                                    <td>{{ $te->spin_ratio }}</td>
                                    <td>
                                        <a href="{{ $app['url_generator']->generate('admin.user-battle-result', ['user' => $user->id, 't_id' => $te->tournament_id]) }}">Result</a> /
                                        <a href="{{ $app['url_generator']->generate('admin.user-battle-bets-and-wins', ['user' => $user->id, 'mp' => 1, 't_id' => $te->tournament_id]) }}">Bets&Wins</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div><!-- /.tab-pane -->
                </div><!-- /.tab-content -->
            </div>

        </div><!-- nav-tabs-custom -->
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "columnDefs": [{"targets": 15, "orderable": false, "searchable": false}],
                "drawCallback": function( settings ) {
                    $(this).wrap( "<div class='table-responsive'></div>" );
                }
            });
        });
    </script>
@endsection
