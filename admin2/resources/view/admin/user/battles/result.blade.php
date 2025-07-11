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
            <li class="active"><a>Battle [ID #{{ $tournament_id }}] Results</a></li>
            <li><a href="{{ $app['url_generator']->generate('admin.user-battle-bets-and-wins', ['user' => $user->id, 'mp' => 1, 't_id' => $tournament_id]) }}">Battle [ID #{{ $tournament_id }}] Bets and wins</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active">
                <table id="user-datatable" class="table table-responsive table-striped table-bordered dt-responsive">
                    <thead>
                    <tr>
                        <th>Entry ID</th>
                        <th>User Id</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Result Place</th>
                        <th>Won amount</th>
                        <th>Win Amount</th>
                        <th>Cash Balance</th>
                        <th>Spins Left</th>
                        <th>Rebuy Times</th>
                        <th>Rebuy Cost</th>
                        <th>Updated At</th>
                        <th>Highest Score At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($tournament_entries as $te)
                        <tr @if($te->user->username == $user->username) style="background-color: #00e765" @endif>
                            <td>{{ $te->id }}</td>
                            <td>{{ $te->user->id }}</td>
                            <td>{{ $te->user->firstname }}</td>
                            <td>{{ $te->user->lastname }}</td>
                            <td>{{ $te->result_place }}</td>
                            <td>{{ $te->won_amount }}</td>
                            <td>{{ $te->win_amount }}</td>
                            <td>{{ $te->cash_balance }}</td>
                            <td>{{ $te->spins_left }}</td>
                            <td>{{ $te->rebuy_times }}</td>
                            <td>{{ $te->rebuy_cost }}</td>
                            <td>{{ $te->updated_at }}</td>
                            <td>{{ $te->highest_score_at }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div><!-- /.tab-pane -->
        </div><!-- /.tab-content -->
    </div><!-- nav-tabs-custom -->
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-datatable").DataTable({
                "pageLength": 50,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]]
            });
        });
    </script>
@endsection