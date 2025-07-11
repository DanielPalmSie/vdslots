@extends('admin.layout')

@section('content')
<div class="container-fluid">
    @include('admin.messaging.partials.topmenu')

    <style>
        @media (min-width: 768px) {
            .modal-dialog {
                width: 80%;
                margin: 30px auto;
            }
        }

        @media (min-width: 992px) {
            .modal-lg {
                width: 100%;

            }
        }
        .progress {
            height:40px;
        }
        .progress-bar {
            padding: 10px;
            font-size: 16px;
        }
    </style>

    <div class="card">
        <div class="nav-tabs-custom">

            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-contacts') }}">All Contacts</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-filters') }}">Contact Filter Lists</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.new-filter-form') }}"><i class="fa fa-plus-square"></i> Create Filter List</a></li>
                <li class="nav-item"><a class="nav-link active">List Segments</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.form') }}"><i class="fa fa-plus-square"></i> Create Segment</a></li>
            </ul>

            <div class="tab-content p-3">
                <table id="segments-list" class="table table-striped table-bordered" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Number of groups</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($segments as $segment)

                        <tr>
                            <td>{{ $segment['id'] }}</td>
                            <td>{{ $segment['name'] }}</td>
                            <td>{{ $segment['description'] }}</td>
                            <td>{{ count($segment['groups']) }}</td>
                            <td>
                                <a class="details" data-object="{{json_encode($segment)}}"><i class="fa fa-list"></i> Details</a>
                                @if(p('messaging.segments.edit'))
                                    - <a href="{{ $app['url_generator']->generate('messaging.segments.edit', ['segment' => $segment['id']]) }}">
                                        <i class="fa fa-edit"></i> Edit</a>
                                @endif
                                @if(p('messaging.contacts.delete'))
                                    - <a class="href-confirm" data-message="Are you sure you want to delete this segment?" href="{{ $app['url_generator']->generate('messaging.segments.delete', ['segment' => $segment['id']]) }}">
                                        <i class="fa fa-trash"></i> Delete</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Segment<span class="segment-name"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <div class="modal-body">
                <div class="progress"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@include("admin.partials.href-confirm")

@section('footer-javascript')
    @parent

    @include('admin.partials.jquery-ui-cdn')

    <script>

        function stringToColour(str) {
            return [
                "#3F5D7D",
                "#279B61",
                "#008AB8",
                "#993333",
                "#A3E496",
                "#95CAE4",
                "#CC3333",
                "#FFCC33",
                "#FFFF7A",
                "#CC6699",
                "#000000"
            ][str];
        }

        function addProgress(group, percent, i) {
            $progress = $('<div class="progress-bar" role="progressbar"><span class="name"><span class="group-name"></span></span></div>');
            $progress.find('.group-name').text(group.name);
            $progress.css('width', percent + "%");
            $progress.css('background-color', stringToColour(i));

            $("#myModal .progress").append($progress);
        }
    </script>

    <script>
        $("#segments-list").DataTable({
            "pageLength": 25,
            "language": {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            },
            "order": [[ 0, "desc"]],
            "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
        });
        $(".details").click(function() {
            var segment = $(this).data('object');

            $("#myModal .modal-title .segment-name").text(segment.name);

            $("#myModal .progress").html();

            var total_covered = 0;

            segment.groups.forEach(function(group, i) {
                var percent = (group.users_covered * 100) /segment.users_count;
                addProgress(group, percent, i);
                total_covered += percent;
            });

            addProgress({name: 'left out'}, 100 - total_covered, 10);

            $("#myModal").modal('show');
        })
    </script>

@endsection
