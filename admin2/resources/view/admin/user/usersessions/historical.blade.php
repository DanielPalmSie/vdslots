@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.filters.date-range-filter', ['date_format' => 'date', 'full' => true])
    <div class="card">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="nav-item border-top border-primary"><a class="nav-link active">Historical User Sessions</a></li>
            </ul>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="table-responsive">
                            <table id="user-datatable" class="table table-striped table-bordered dt-responsive"
                                   cellspacing="0" width="100%">
                                <thead>
                                <tr>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th>Ended At</th>
                                    <th>Equipment</th>
                                    <th>End Reason</th>
                                    <th>IP</th>
                                    <th>FingerPrint</th>
                                    <th>Deposit Sum</th>
                                    <th>Withdrawal Sum</th>
                                    <th>Bet Sum</th>
                                    <th>Win Sum</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($user_sessions as $us)
                                    <tr>
                                        <td>{{ $us->created_at }}</td>
                                        <td>{{ $us->updated_at }}</td>
                                        <td>{{ $us->ended_at }}</td>
                                        <td>{{ $us->equipment }}</td>
                                        <td>{{ $us->end_reason }}</td>
                                        <td>{{ $us->ip }}</td>
                                        <td>{{ $us->fingerprint }}</td>
                                        <td>{{ nfCents($us->deposit_sum) }}</td>
                                        <td>{{ nfCents($us->withdrawal_sum) }}</td>
                                        <td>{{ nfCents($us->bet_sum) }}</td>
                                        <td>{{ nfCents($us->win_sum) }}</td>
                                        <td>
                                            <a href="{{ $app['url_generator']->generate('admin.user-actions', ['user' => $user->id]) }}?start_date={{ $us->created_at  }}&end_date={{ $us->ended_at }}">Actions</a> |
                                            <a href="{{ $app['url_generator']->generate('admin.user-betsandwins-all', ['user' => $user->id, 'ext_start_date' => $us->created_at, 'ext_end_date' => $us->ended_at]) }}">Game History</a>
                                        </td>
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
                "order": [[ 0, "desc"]]
            });
        });
    </script>

@endsection
