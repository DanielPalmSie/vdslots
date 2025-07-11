@extends('admin.layout')

@section('content')
    @include('admin.user.partials.topmenu')

    <form id="risk-score-report-filter" action="{{ $app['url_generator']->generate('admin.user-risk-score-report') }}" method="get">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="box-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        @include('admin.filters.user-id-filter')
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-country">Country</label>
                        <select name="country" id="select-country" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
                            <option value="all" selected>All</option>
                            @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                                @if(!in_array($country['iso'], $exclude_countries))
                                    <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    @include('admin.filters.date-range-filter', ['date_range' => $period_date['second'], 'date_format' => 'date', 'input_name' => "Second period date range", 'input_id' => '-end'])
                    @include('admin.filters.date-range-filter', ['date_range' => $period_date['first'], 'date_format' => 'date', 'input_name' => "First period date range", 'input_id' => '-start'])
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-trigger-type">Trigger Type</label>
                        <select name="trigger-type" id="select-trigger-type" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Shows all types if not selected" data-allow-clear="true">
                            <option value="all">All</option>
                            <option value="aml">AML</option>
                            <option value="rg">RG</option>
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
            <h3 class="card-title">User Risk Score</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive"
                       id="risk-score-report-datatable" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>User ID</th>
                        <th></th>
                        <th>Country</th>
                        <th>Second period score</th>
                        <th>First period score</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($page['data'] as $element)
                        <tr>
                            <td>{{ $element->user_id }}</td>
                            <td>{{ $element->declaration_proof }}</td>
                            <td>{{ $element->country }}</td>
                            <td>{{ $element->second }}</td>
                            <td>{{ $element->first }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <style>
        .color-box {
            width: 20px;
            height: 20px;
            display: inline-block;
            float: left;
            margin-right: 5px;
        }
        .color-box.red {
            background: red;
        }
        .color-box.blue {
            background: blue;
        }
        .color-box.orange {
            background: yellow;
        }
        .color-box.green {
            background: green;
        }
        .color-box.purple {
            background: purple;
        }
        .color-box.black {
            background: black;
        }
    </style>

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
                "url" : "{{ $app['url_generator']->generate('admin.user-risk-score-report') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#risk-score-report-filter').serializeArray();
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
                    "defaultContent": "",
                    "render": function (data) {
                        return '<a target="_blank" href="' + username_url + data + '/">' + data + '</a>';
                    },
                },
                {
                    "data": "declaration_proof",
                    "defaultContent": "",
                    "render": function (data) {
                        if (data === null || !data.length) {
                            return "";
                        }

                        return (data[0] === '1' ? '<span class="color-box blue prevent-parent-background" title="Declaration of Source of Wealth"></span>' : '')
                            + (data[1] === '1' ? '<span class="color-box red prevent-parent-background" title="Proof of Source of Wealth"></span>' : '')
                            + (data[2] === '1' ? '<span class="color-box orange prevent-parent-background" title="Forced Loss Limit"></span>' : '')
                            + (data[3] === '1' ? '<span class="color-box green prevent-parent-background" title="Forced Bet Limit"></span>' : '')
                            + (data[4] === '1' ? '<span class="color-box purple prevent-parent-background" title="Forced Deposit Limit"></span>' : '')
                            + (data[5] === '0' ? '<span class="color-box black prevent-parent-background" title="Blocked"></span>' : '');
                    },
                    orderable: false
                },
                { "data": "country", "defaultContent": ""},
                { "data": "second", "defaultContent": ""},
                { "data": "first" , "defaultContent": ""}
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 4, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#risk-score-report-datatable").wrap( "<div class='table-responsive'></div>" );
            };
            var table = $("#risk-score-report-datatable").DataTable(table_init);
        });
    </script>
@endsection
