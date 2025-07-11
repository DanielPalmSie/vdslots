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
            <h3 class="card-title">Notification History</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-datatable" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Date</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($notifications_list as $notification)
                        <tr>
                            <td class="text-center">
                                <img style="width: 35px; height: 35px;" src="{{ phive('UserHandler')->eventImage($notification) }}" alt="Event Image">
                            </td>
                            <td>{{ \Carbon\Carbon::parse($notification->created_at)->format('Y-m-d H:i:s') }}</td> <!-- Format date as needed -->
                            <td>{!! phive('UserHandler')->eventString($notification, 'you.') !!}</td> <!-- Safely render HTML content -->
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
            $("#user-datatable").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "columnDefs": [{"targets": 0, "orderable": false, "searchable": false}]
            });
        });
    </script>
@endsection
