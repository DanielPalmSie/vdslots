@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.partials.date-filter')
    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">Game Statistics</h3>
        </div><!-- /.box-header -->
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-datatable" class="table table-striped table-bordered dt-responsive"
                       cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Game</th>
                        <th>Device Type</th>
                        <th>Bets</th>
                        <th>Wins</th>
                        <th>Overall Gross</th>
                        <th>FRB Wins</th>
                        <th>JP Fee</th>
                        <th>Op. Fees</th>
                        <th>Site Gross</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($game_statistics as $stat)
                        <tr>
                            <td>{{ $stat->game_name }}</td>
                            <td>{{ $stat->device_type }}</td>
                            <td>{{ nfCents($stat->bets) }}</td>
                            <td>{{ nfCents($stat->wins) }}</td>
                            <td>{{ nfCents($stat->overall_gross) }}</td>
                            <td>{{ nfCents($stat->frb_wins) }}</td>
                            <td>{{ nfCents($stat->jp_fee) }}</td>
                            <td>{{ nfCents($stat->op_fee) }}</td>
                            <td>{{ nfCents($stat->site_gross) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div><!-- /.box-body -->
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
