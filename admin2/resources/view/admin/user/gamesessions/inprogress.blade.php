@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <div class="card">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-game-sessions-historical', ['user' => $user->id]) }}">Historical Game Sessions
                    </a>
                </li>
                <li class="nav-item border-top border-primary">
                    <a class="nav-link active ">Game Sessions In Progress</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-game-sessions-logged', ['user' => $user->id]) }}">Logged Sessions
                    </a>
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
                                    <th>Game</th>
                                    <th>Wager Total</th>
                                    <th>Win Total</th>
                                    <th>Res Total</th>
                                    <th>Start Balance</th>
                                    <th>End Balance</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>IP Address</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($game_sessions as $game_session)
                                    <tr>
                                        <td>{{ $game_session->game_ref }}</td>
                                        <td>{{ \App\Helpers\DataFormatHelper::nf($game_session->bet_amount) }}</td>
                                        <td>{{ \App\Helpers\DataFormatHelper::nf($game_session->win_amount) }}</td>
                                        <td>{{ \App\Helpers\DataFormatHelper::nf($game_session->result_amount) }}</td>
                                        <td>{{ \App\Helpers\DataFormatHelper::nf($game_session->balance_start) }}</td>
                                        <td>{{ \App\Helpers\DataFormatHelper::nf($game_session->balance_end) }}</td>
                                        <td>{{ $game_session->start_time }}</td>
                                        <td>{{ $game_session->end_time }}</td>
                                        <td>{{ $game_session->ip }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
