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
            Clash of spins
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-transactions-datatable"
                       class="table table-striped table-bordered dt-responsive" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <th>Start time</th>
                        <th>End time</th>
                        <th>Spins</th>
                        <th>Position</th>
                        <th>Prize</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($casinoRaces as $cr)
                        <tr>
                            <td>{{ $cr->start_time }}</td>
                            <td>{{ $cr->end_time }}</td>
                            <td>{{ $cr->spins }}</td>
                            <td>{{ $cr->position }}</td>
                            <td>{{ phive('Trophy')->getAward(explode(',', $cr->prize)[0])['description'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-transactions-datatable").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]]
            });
        });
    </script>

@endsection
