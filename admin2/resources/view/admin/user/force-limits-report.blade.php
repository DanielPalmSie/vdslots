@extends('admin.layout')

@section('content')
    @include('admin.user.partials.topmenu')

    <form id="force-limits-report-filter" action="{{ $app['url_generator']->generate('admin.user-force-limits-report') }}" method="get">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    {{--@include('admin.filters.date-range-filter', ['date_format' => 'date'])--}}
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
                        <label for="select-limit-type">Limit Type</label>
                        <select name="limit-type" id="select-limit-type" class="form-control selegct2-class"
                                style="width: 100%;" data-placeholder="Shows all if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            @foreach(\App\Helpers\DataFormatHelper::getLimitsNames() as $key => $text)
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
            <h3 class="card-title">Forced RG Limits Report</h3>
        </div><!-- /.box-header -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive"
                       id="force-limits-report-datatable" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>User Id</th>
                        <th>Country</th>
                        <th>Limit Type</th>
                        <th>Limit Setting</th>
                        <th>Set Limit</th>
                        <th>Remaining Limit</th>
                        <th>Currency</th>
                        <th>Expiration time of force limit period</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($page['data'] as $element)
                        <tr>
                            <td>{{ $element->id }}</td>
                            <td>{{ $element->country }}</td>
                            <td>{{ $element->limit_type }}</td>
                            <td>{{ $element->current_duration }}</td>
                            <td>{{ $element->current_limit }}</td>
                            <td>{{ $element->remaining }}</td>
                            <td>{{ $element->currency }}</td>
                            <td>{{ $element->force_expiration_time }}</td>
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
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country', 'GB') }}").change();
            $("#select-limit-type").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('limit-type', 'all') }}").change();
        });
    </script>
    <script>
        $(function () {
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-force-limits-report') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#force-limits-report-filter').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            table_init['columns'] = [
                {
                    "data": "id",
                    "render": function ( data ) {
                        return '<a target="_blank" href=' + username_url + data + '/>' + data + '</a>';
                    }
                },
                { "data": "country" },
                { "data": "limit_type" },
                { "data": "current_duration" },
                { "data": "current_limit" },
                { "data": "remaining" },
                { "data": "currency" },
                { "data": "force_expiration_time" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 7, 'asc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#force-limits-report-datatable").wrap( "<div class='table-responsive'></div>" );
            };
            var table = $("#force-limits-report-datatable").DataTable(table_init);
        });
    </script>
@endsection
