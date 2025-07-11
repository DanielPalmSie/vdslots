@extends('admin.layout')

@section('content')
    @include('admin.user.partials.topmenu')
    <?php
        $is_date_used = $app['request_stack']->getCurrentRequest()->get('risk-group', 'all') == 'all';
        if ($is_date_used) {
            $selected_date = empty($app['request_stack']->getCurrentRequest()->get('trigger-day')) ? \Carbon\Carbon::now()->toDateString() : $app['request_stack']->getCurrentRequest()->get('trigger-day');
        }

    ?>

    <form id="follow-up-report-filter" action="{{ $app['url_generator']->generate('admin.user-follow-up-report') }}" method="get">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-4 col-lg-2 col-xlg-2 col-fhd-2">
                        <label>Date</label>
                        <input autocomplete="off" data-provide="datepicker" data-date-format="yyyy-mm-dd" type="text" name="trigger-day"
                               class="form-control datepicker" placeholder="{{$is_date_used ? 'Select a day' : 'Not used when risk-group is set' }}"
                               value="{{ $selected_date }}">
                    </div>
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
                        <label for="select-trigger-time">Time Period</label>
                        <select name="trigger-time" id="select-trigger-time" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Shows all types if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            @foreach(\App\Helpers\DataFormatHelper::getFollowUpOptions() as $key => $text)
                                <option value="{{ $key }}">{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-trigger-type">Trigger Type</label>
                        <select name="trigger-type" id="select-trigger-type" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Shows all types if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            <option value="rg">Responsible Gambling</option>
                            <option value="aml">AML</option>
                        </select>
                    </div>
                    <div class="form-group  col-6 col-md-4 col-lg-2">
                        <label for="select-risk-group">Risk group</label>
                        <select name="risk-group" id="select-risk-group" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="No risk group" data-allow-clear="true">
                            <option value="all">All</option>
                            @foreach(\App\Helpers\DataFormatHelper::getFollowUpGroups(null, 'aml') as $key => $text)
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
            <h3 class="card-title">User Follow Up Report</h3>
        </div><!-- /.box-header -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive"
                       id="follow-up-report-datatable" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Country</th>
                        <th>Trigger Type</th>
                        <th>Trigger Setting</th>
                        <th>Created At</th>
                        <th>Last login</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($page['data'] as $element)
                        <tr>
                            <td>{{ $element->user_id }}</td>
                            <td>{{ $element->country }}</td>
                            <td>{{ $element->category }}</td>
                            <td>{{ $element->period }}</td>
                            <td>{{ $element->created_at }}</td>
                            <td>{{ $element->last_login }}</td>
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
            $("#select-trigger-time").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('trigger-time', 'all') }}").change();
            $("#select-risk-group").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('risk-group') }}").change();
        });
    </script>
    <script>
        String.prototype.capitalize = function() {
            var string = this.toString();
            return string.charAt(0).toUpperCase() + string.slice(1);
        };

        String.prototype.mapAndCapitalize = function(render_map) {
            return this.toString().split(',').map(function(el) {
                return render_map[el.trim()] ? render_map[el.trim()].capitalize() : el.trim().capitalize();
            }).filter(function(el,i,a){
                if(i==a.indexOf(el))
                    return 1;
                return 0;
            }).join(', ');
        };

        $(function () {
            var render_map = {
                'rg': 'Responsible Gambling',
                'aml': 'AML',
                'fr': 'Fraud',
                'halfyearly': 'Half Yearly'
            };
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-follow-up-report') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#follow-up-report-filter').serializeArray();
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
                {
                    "data": "category",
                    "render": function (data) {
                        return data.mapAndCapitalize(render_map);
                    }
                },
                {
                    "data": "period",
                    "render": function (data) {
                        return data.mapAndCapitalize(render_map);
                    }
                },
                { "data": "created_at" },
                { "data": "last_login" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 5, 'asc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;

            var table = $("#follow-up-report-datatable").DataTable(table_init);
        });
    </script>
@endsection
