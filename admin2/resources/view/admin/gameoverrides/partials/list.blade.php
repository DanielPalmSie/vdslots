<div class="card">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active">
                    Games Overridden
                </a>
            </li>
        </ul>
{{--    </div>--}}
{{--    <div class="card-body">--}}
        <div class="tab-content p-3">
            <div class="tab-pane active">
                <table id="table-datatable" class="table responsive table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Id</th>
                        <th>Name</th>
                        <th>Ext game name / id</th>
                        <th>Ext launch id / id</th>
                        @if(!$show_group)
                            <th>RTP</th>
                            <th>RTP Modifier</th>
                        @endif
                        <th>Device Type</th>
                        <th>Device Type Num.</th>
                        @if($show_group)
                            <th># Overrides</th>
                        @endif
                        <th></th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($games as $g)
                        <tr>
                            <td class="align-middle">{{ $g->id }}</td>
                            <td>{{ $g->game_name }}</td>
                            <td>{{ $g->ext_game_name }}</td>
                            <td>{{ $g->game_id }}</td>
                            @if(!$show_group)
                                <td>{{ $g->payout_percent }}</td>
                                <td>{{ $g->payout_extra_percent }}</td>
                            @endif
                            <td>{{ $g->device_type }}</td>
                            <td>{{ $g->device_type_num }}</td>
                            @if($show_group)
                                <td>{{ $g->total_overridden }}</td>
                            @endif
                            <td>
                                <a href="{{ $app['url_generator']->generate('games-list-overrides') }}?game_id={{ $g->id }}">List</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#table-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[0, "desc"]],
                "columnDefs": [{"targets": 7, "orderable": false, "searchable": false}],
                "drawCallback": function( settings ) {
                    $(this).wrap( "<div class='table-responsive'></div>" );
                }
            });
        });
    </script>
@endsection
