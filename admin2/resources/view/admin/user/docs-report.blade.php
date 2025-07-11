@extends('admin.layout')

@section('content')
    @include('admin.user.partials.topmenu')

    <form id="docs-report-filter" action="{{ $app['url_generator']->generate('admin.user-docs-report') }}" method="get">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-doc-tag">Document Type</label>
                        <select name="doc-tag" id="select-doc-tag" class="form-control selegct2-class"
                                style="width: 100%;" data-placeholder="Shows all if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            @foreach(\App\Classes\Dmapi::getDocumentType() as $key => $text)
                                <option value="{{ $key }}">{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="target">User Id</label>
                        <input type="text" name="target" class="form-control" placeholder="Customer user id"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('target') }}">
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-agent">Agent</label>
                        <select name="agent" id="select-agent" class="form-control selegct2-class"
                                style="width: 100%;" data-placeholder="Shows all if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            @foreach($docs['agents'] as $key => $text)
                                <option value="{{ $key }}">{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-info">Search</button>
            </div>
        </div>
    </form>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Documents Management Report</h3>
        </div><!-- /.box-header -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive"
                       id="docs-report-datatable" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Created At</th>
                        <th>File Uploaded</th>
                        <th>User Id</th>
                        <th>Executed At</th>
                        <th>Executed By</th>
                        <th>Type of Document</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($docs['data'] as $element)
                        <tr>
                            <td>{{ $element['created_at'] }}</td>
                            <td>{{ $element['file_uploaded'] }}</td>
                            <td>{{ $element['user_id'] }}</td>
                            <td>{{ $element['executed_on'] }}</td>
                            <td>{{ $element['executed_by'] }}</td>
                            <td>{{ $element['tag'] }}</td>
                            <td>{{ $element['status'] }}</td>
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
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-doc-tag").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('doc-tag', 'all') }}").change();
            $("#select-agent").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('agent', 'all') }}").change();
        });
    </script>
    <script>
        $(function () {
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-docs-report') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#docs-report-filter').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            table_init['columns'] = [
                { "data": "created_at" },
                { "data": "file_uploaded" },
                {
                    "data": "user_id",
                    "render": function ( data ) {
                        return '<a target="_blank" href=' + username_url + data + '/documents/>' + data + '</a>';
                    }
                },
                { "data": "executed_on" },
                { "data": "executed_by" },
                { "data": "tag" },
                { "data": "status" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 2, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $docs['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#docs-report-datatable").wrap( "<div class='table-responsive'></div>" );
            };
            var table = $("#docs-report-datatable").DataTable(table_init);
        });
    </script>
@endsection
