@extends('admin.layout')

@section('content')
    @include('admin.user.partials.topmenu')

    <form id="monitored-accounts-report-filter" action="{{ $app['url_generator']->generate('admin.user-monitored-accounts-report') }}" method="get">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="username">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Part of username"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('username') }}">
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-country">Country</label>
                        <select name="country" id="select-country" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                                <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-trigger-type">Trigger Type</label>
                        <select name="trigger-type" id="select-trigger-type" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Shows all if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            @foreach(\App\Helpers\DataFormatHelper::getManualFlags() as $key => $text)
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
            <h3 class="card-title">Monitored Accounts</h3>
        </div><!-- /.box-header -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive"
                       id="monitored-accounts-report-datatable" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Country</th>
                        <th>Trigger Type</th>
                        <th>Created At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($page['data'] as $element)
                        <tr>
                            <td>{{ $element->user_id }}</td>
                            <td>{{ $element->country }}</td>
                            <td>{{ $element->setting }}</td>
                            <td>{{ $element->created_at }}</td>
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
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country', 'all') }}").change();
            $("#select-trigger-type").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('trigger-type', 'all') }}").change();
        });
    </script>
    <script>
        $(function () {
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-monitored-accounts-report') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#monitored-accounts-report-filter').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            table_init['columns'] = [
                {
                    "data": "user_id",
                    "render": function ( data ) {
                        return '<a target="_blank" href="' + username_url + data + '/">' + data + '</a>';
                    }
                },
                { "data": "country" },
                { "data": "setting" },
                { "data": "created_at" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 3, 'asc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#monitored-accounts-report-datatable").wrap( "<div class='table-responsive'></div>" );
            };
            var table = $("#monitored-accounts-report-datatable").DataTable(table_init);
        });
    </script>
@endsection
