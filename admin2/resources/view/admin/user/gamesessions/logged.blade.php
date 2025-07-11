@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.partials.date-filter')
    <div class="card">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-game-sessions-historical', ['user' => $user->id]) }}">Historical
                        Game Sessions</a></li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-game-sessions-inprogress', ['user' => $user->id]) }}">Game Sessions In Progress</a>
                </li>
                <li class="nav-item border-top border-primary">
                    <a class="nav-link active">Logged Sessions</a>
                </li>
            </ul>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="table-responsive">
                            <table id="user-datatable" class="table table-striped table-bordered dt-responsive"
                                   cellspacing="0" width="100%">
                                <thead>
                                <tr>
                                    <th>Created at</th>
                                    <th>Updated at</th>
                                    <th>Ended at</th>
                                    <th>Equipment</th>
                                    <th>End reason</th>
                                    <th>IP Address</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($in_outs as $in_out)
                                    <tr>
                                        <td>{{ $in_out->created_at }}</td>
                                        <td>{{ $in_out->updated_at }}</td>
                                        <td>{{ $in_out->ended_at == '0000-00-00 00:00:00' ? "In progress" : $in_out->ended_at }}</td>
                                        <td>{{ $in_out->equipment }}</td>
                                        <td>{{ $in_out->end_reason }}</td>
                                        <td>{{ $in_out->ip }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
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
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]]
            });
        });
    </script>

@endsection
