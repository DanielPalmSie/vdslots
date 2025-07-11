@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')

        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link @if(empty($app['request_stack']->getCurrentRequest()->get('filter-id'))) active @endif">All Contacts</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-filters') }}">Contact Filter Lists</a></li>
                    @if(!empty($app['request_stack']->getCurrentRequest()->get('filter-id')))
                        <li class="nav-item"><a class="nav-link active">Filter [{{ $page['named_search']->name }}] contact list</a></li>
                    @endif
                    @if(p('messaging.contacts.new'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.new-filter-form') }}"><i class="fa fa-plus-square"></i> Create Filter List</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.list') }}">List Segments</a></li>

                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.form') }}"><i class="fa fa-plus-square"></i> Create Segment</a></li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        <table id="contact-list-datatable" class="table table-striped table-bordered table-responsive"
                            cellspacing="0" width="100%">
                            <thead>
                            <tr>
                                <th>Registered Date</th>
                                <th>User Id</th>
                                <th>Email</th>
                                <th>Mobile Phone</th>
                                <th>Bonus Fraud Flag</th>
                                <th>Full Name</th>
                                <th>Country</th>
                                <th>Language</th>
                                <th>Currency</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($page['data'] as $element)
                                <tr>
                                    <td>{{ $element->register_date }}</td>
                                    <td>{{ $element->id }}</td>
                                    <td>{{ $element->email }}</td>
                                    <td>{{ $element->mobile }}</td>
                                    <td>{{ $element->bonus_fraud_flag }}</td>
                                    <td>{{ $element->full_name }}</td>
                                    <td>{{ $element->country }}</td>
                                    <td>{{ $element->language }}</td>
                                    <td>{{ $element->currency }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if ($load_type === 1)
            <span class="load-{{$load_type}}">
                <span class="query_data load-{{$load_type}}" data-value="{{$page['named_search']->form_params}}"></span>
                <span class="selected_fields load-{{$load_type}}" data-value="{{json_encode($page['selected_fields'])}}"></span>
            </span>
        @endif

    </div>

@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        if ($(".load-1").length > 0) {
            dataTable = $("#contact-list-datatable");

            dataTable.empty();

            var selected_fields = $(".load-1.selected_fields").data('value');
            var table = dataTable.DataTable({
                processing  : true,
                serverSide  : true,
                ajax        : {
                    "url": "{{ $app['url_generator']->generate('messaging.contact.list-contacts', ['filter-id' => $app['request_stack']->getCurrentRequest()->get('filter-id')]) }}",
                    type: "POST",
                    data: function (d) {
                        d.query_data = $(".load-1.query_data").data('value');
                        d.selected_fields = Object.keys(selected_fields);
                    }
                },
                columns     : Object.keys(selected_fields).map(function (entry) {
                    return {"data": entry, "title": selected_fields[entry]}
                }),
                searching   : false,
                order       : [[0, 'desc']],
                deferLoading: parseInt("{{ 50 }}"),
                pageLength  : 25,
//                sDom: '<"H"ilr><"clear">t<"F"p>'
            });

            table.ajax.reload();
        } else {
            $(function () {
                var table_init = {};
                table_init['processing'] = true;
                table_init['serverSide'] = true;
                table_init['ajax'] = {
                    "url": "{{ $app['url_generator']->generate('messaging.contact.list-contacts', ['filter-id' => $app['request_stack']->getCurrentRequest()->get('filter-id')]) }}",
                    "type": "POST"//,
                    //"data": function(d){
                    //d.form = $('#filter-form-user-action').serializeArray();
                    //}
                };
                table_init['language'] = {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                };
                var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
                table_init['columns'] = [
                    {"data": "register_date"},
                    {
                        "data": "id",
                        "render": function (data) {
                            return '<a target="_blank" href="' + username_url + data + '/">Go to the back office</a>';
                        },
                        orderable: false
                    },
                    {"data": "email"},
                    {"data": "mobile"},
                    {
                        "data": "bonus_fraud_flag",
                        "render": function (data) {
                            if (data == '1') {
                                return 'Yes';
                            } else {
                                return 'No';
                            }
                        }
                    },
                    {"data": "full_name"},
                    {"data": "country"},
                    {"data": "language"},
                    {"data": "currency"}
                ];
                table_init['searching'] = false;
                table_init['order'] = [[0, 'desc']];
                table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
                table_init['pageLength'] = 25;
                table_init['drawCallback'] = function (settings) {
                    $("#contact-list-datatable").wrap("<div class='table-responsive'></div>");
                };

                var table = $("#contact-list-datatable").DataTable(table_init);

            });
        }
    </script>
@endsection
