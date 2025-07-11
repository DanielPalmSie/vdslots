@extends('admin.layout')
<?php
$u = cu($user->username);
?>

@section('header-css') @parent
<link rel="stylesheet" href="/phive/admin/customization/plugins/bootstrap4-editable/css/bootstrap-editable.css">
<link rel="stylesheet" href="/phive/admin/customization/styles/css/wheel.css" type="text/css"/>
<link rel="stylesheet" href="/phive/admin/customization/styles/css/xeditableselect2.css" type="text/css"/>
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.user.partials.header.actions')
        @include('admin.user.partials.header.main-info')
        <form id="risk-score-report-filter" method="get">
            <div class="card border-top border-top-3">
                <div class="card-body">
                    <div class="row">
                        @include('admin.filters.date-range-filter', ['date_range' => $date_range, 'date_format' => 'date', 'input_name' => "Date range", 'input_id' => '-start'])
                        <div class="form-group col-6 col-md-4 col-lg-3">
                            <label for="select-wheel">Wheel Type</label>
                            <select name="wheel" id="select-wheel" class="form-control select2-class"
                                    style="width: 100%;" data-placeholder="Shows all wheels if not selected"
                                    data-allow-clear="true">
                                <option value="all" selected>All</option>
                                @foreach($jackpot_wheels as $jackpot_wheel)
                                    <option value="{{ $jackpot_wheel->id }}">{{ $jackpot_wheel->name }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info">Search</button>
                </div>
            </div>
        </form>
        <div class="card card-primary border border-primary">
            <div class="card-header">
                The Wheel Of Jackpots History
            </div>

            <div id='wheel-spins' class="row">
                <div class="col-12">
                    <div class="card-body">
                        <table class="table table-bordered" id="wheel-spins-table">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Wheel</th>
                                <th>Winning</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($page['data'] as $row)
                                <tr>
                                    <td>{{ $row->created_at }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>
                                        {!! $row->description !!}
                                    </td>
                                    <td>
                                        {!!  $row->link !!}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="wheel-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title pull-left">Wheel replay</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body" id="wheelExtContainer">
                            @include('admin.gamification.wheelofjackpots.partials.preview_wheel',
                            ['wheel_style'=> ['name'=> $row] ]
                            )
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success lower-modal-save-btn save"
                                data-toggle="modal"
                                data-target="#wheel-modal">Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            console.log("{{ $selected_wheel }}");
            $("#select-wheel").select2().val("{{ $selected_wheel }}");
            $('#select-wheel').trigger('change');

            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url": "{{ $app['url_generator']->generate('admin.user-wheel-of-jackpot-history', ['user' => $user->id]) }}" + location.search,
                "type": "POST",
                "data": function (d) {
                    d.form = $('#wheel-spins-table').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found."
            };
            table_init['columns'] = [
                {"data": "created_at"},
                {"data": "name"},
                {
                    "data": "description",
                    orderable: false
                },
                {
                    "data": "link",
                    orderable: false
                }
            ];
            table_init['searching'] = false;
            table_init['order'] = [[0, 'desc']];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['drawCallback'] = function (settings) {
                $("#wheel-spins-table").wrap("<div class='table-responsive'></div>");
            };
            var table = $("#wheel-spins-table").DataTable(table_init);

        });

    </script>

@endsection
