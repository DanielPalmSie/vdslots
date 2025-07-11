@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')

        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-contacts') }}">All Contacts</a></li>
                    <li class="nav-item"><a class="nav-link active">Contact Filter Lists</a></li>
                    @if(p('messaging.contacts.new'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.new-filter-form') }}"><i class="fa fa-plus-square"></i> Create Filter List</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.list') }}">List Segments</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.form') }}"><i class="fa fa-plus-square"></i> Create Segment</a></li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        <table id="filter-list-databable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Contacts</th>
                                <th>Language</th>
                                <th>Currency</th>
                                <th>Created at</th>
                                <th>Updated at</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($namedSearches as $namedSearch)
                                <tr>
                                    <td>{{ $namedSearch['id'] }}</td>
                                    <td>{{ $namedSearch['name'] }}</td>
                                    <td>{{ $namedSearch['result'] }}</td>
                                    <td>{{ $namedSearch['language'] }}</td>
                                    <td>{{ empty($namedSearch['currency']) ? 'All' : $namedSearch['currency'] }}</td>
                                    <td>{{ $namedSearch['created_at'] }}</td>
                                    <td>{{ $namedSearch['updated_at'] }}</td>
                                    <td><a href="{{ $app['url_generator']->generate('messaging.contact.list-contacts', ['filter-id' => $namedSearch['id']]) }}">
                                            <i class="fa fa-list"></i> See results</a>
                                        @if(p('messaging.contacts.new'))
                                            - <a href="{{ $app['url_generator']->generate('messaging.contact.clone-filter', ['namedSearch' => $namedSearch['id']]) }}">
                                                <i class="fa fa-clone"></i> Clone</a>
                                        @endif
                                        @if(p('messaging.contacts.edit'))
                                            - <a href="{{ $app['url_generator']->generate('messaging.contact.edit-filter', ['namedSearch' => $namedSearch['id']]) }}">
                                                <i class="fa fa-edit"></i> Edit</a>
                                        @endif
                                        @if(p('messaging.contacts.delete'))
                                            - <a class="href-confirm" data-message="Are you sure you want to delete the filter?" href="{{ $app['url_generator']->generate('messaging.contact.delete-filter', ['namedSearch' => $namedSearch['id']]) }}">
                                                <i class="fa fa-trash"></i> Delete</a>
                                        @endif
                                        @if($app['messaging']['allow_contact_filters_download'])
                                            - {!! \App\Repositories\ExportRepository::getExportView($app, 'contacts-list', $namedSearch['id']) !!}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @include("admin.partials.href-confirm")
    </div>

@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#filter-list-databable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 0, "desc"]],
                "columnDefs": [{"targets": 5, "orderable": false, "searchable": false}]
            });
        });
    </script>

@endsection