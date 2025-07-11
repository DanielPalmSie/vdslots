@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.wheelofjackpots.partials.topmenu')

        <form id="filter-form-player-balance" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title">Filters</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                    </div>
                    <input type="hidden" name="wheel_id" value= "{{ $request->get('wheel_id') }}"/>
                </div><!-- /.card-body -->
                <div class="box-footer">
                    <button class="btn btn-info">Search</button>
                </div><!-- /.box-footer-->
            </div>
        </form>

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">The Wheel Of Jackpots Statistics</h3>
                    <div style="float: right">
                        <a href="{{ $app['url_generator']->generate('wheelofjackpots') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                    </div>
            </div>

            <div class="card-body">
                <table class="table table-striped table-bordered">
                    <tr>
                        <th style="width: 5%">Id</th>
                        <th style="width: 20%">Wheel Name</th>
                        <th style="width: 75%">Total Number of Spins</th>
                    </tr>
                    @foreach($wheels as $wheel)
                        <tr>
                            <td>{{ $wheel->id }}</td>
                            <td>
                                @if($wheel->deleted)
                                    {{ $wheel->name }}
                                @else
                                    <a href="{{ $app['url_generator']->generate('wheelofjackpots-update-wheel', ['wheel_id' => $wheel->id]) }}">
                                        {{ $wheel->name }}
                                    </a>
                                @endif
                            </td>
                            <td>{{ $page['recordsTotal'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">The Wheel Of Jackpots Log</h3>
            </div>

            <div class="card-body">
                <table class="fraud-section-datatable table table-striped table-bordered dt-responsive"
                    id="fraud-high-depositors-datatable" cellspacing="0" width="100%">

                @if(count($winSegDesc)==50)
                    <tr>
                        <td colspan="4" align="center">The Wheel Of Jackpots was never spun.</td>
                    </tr>
                @else
                    <thead>
                    <tr>
                        <th style="width: 20%">User ID</th>
                        <th style="width: 20%">Date</th>
                        <th style="width: 20%">Winning Segment</th>
                        <th style="width: 20%">Winning Description</th>
                        <th style="width: 20%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($page['data'] as $key => $element)
                        <tr>
                            <td>{{ $element->user_id }}</td>
                            <td>{{ $element->created_at }}</td>
                            <td>{{ $element->win_segment }}</td>
                            <td>{{ $element->reward_desc }}</td>
                            <td>{{ $element->id }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                @endif
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
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : '{{ $app['url_generator']->generate('wheelofjackpots-wheellog', ['wheel_id' => $wheel->id]) }}' + "&date-range={{ $_GET['date-range'] }}",
                "type" : "POST"
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['columns'] = [
                {
                    "data": "user_id",
                    "render": function (data, type, row) {
                        var route_with_placeholder = '{{ $app['url_generator']->generate('admin.userprofile', ["user" => ':placeholder']) }}';
                        var real_route = route_with_placeholder.replace(":placeholder", row.user_id);
                        return '<a href="' + real_route + '">' + row.user_id + '</a>';
                    }
                },
                { "data": "created_at" },
                { "data": "win_segment" },
                { "data": "reward_desc" },
                {
                    "data": "id",
                    orderable: false,
                    "render": function ( data, type, row ) {
                        var route_with_placeholder = '{{ $app['url_generator']->generate('admin.user-wheel-of-jackpot-history', ["user" => ':placeholder']) }}';
                        var real_route = route_with_placeholder.replace(":placeholder", row.user_id);
                        return '<a href="' + real_route + '?wheel_id={{$wheel->id}}&wheel_log_id=' + data + '&user_id=' + row.user_id +'">Replay The Wheel Of Jackpots</a>';
                    }
                }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 1, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#fraud-high-depositors-datatable").wrap( "<div class='table-responsive'></div>" );
            };

            var table = $("#fraud-high-depositors-datatable").DataTable(table_init);

        });
    </script>
@endsection



