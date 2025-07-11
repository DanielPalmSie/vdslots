@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <form id="filter-form-user-liability">
        <div class="card border-top border-top-3">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-status">Display</label>
                        <select name="status" id="select-type" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="" data-allow-clear="false">
                            <option value="monthly">Monthly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>

                    <div id="month-group" class="form-group col-6 col-md-4 col-lg-2">
                        <label for="select-status">Month</label>
                        <div class="input-group date" id="date-monthly" data-provide="datepicker">
                            <input type="text" class="form-control"
                                   value="{{ empty($app['request_stack']->getCurrentRequest()->get('year')) ? \Carbon\Carbon::now()->format('F-Y') : \Carbon\Carbon::create($app['request_stack']->getCurrentRequest()->get('year'), $app['request_stack']->getCurrentRequest()->get('month'))->format('F-Y') }}">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>

                    <div id="day-group" style="display: none;" class="form-group col-xs-6 col-md-4 col-lg-2">
                        <label for="select-status">Day</label>
                        <div class="input-group date" id="date-daily" data-provide="datepicker">
                            <input type="text" class="form-control" value="{{ \Carbon\Carbon::now()->toDateString() }}">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button data-action="normal" class="btn btn-info liab-search-btn" type="submit">Search</button>
                <button data-action="unallocated" class="btn btn-info liab-search-btn" data-amount="0" id="liab-unallocated-btn" style="display: none;" type="submit">Check unallocated amount</button>
                @if(p(\App\Repositories\LiabilityRepository::PERMISSION_LIABILITY_ADJUST))
                    <a data-action="normal" class="btn btn-warning"
                            href="{{ $app['url_generator']->generate('admin.user-liability-adjust', ['user' => $user->id]) }}">Adjust Liability</a>
                @endif
            </div>
        </div>
    </form>

    <div id="liab-loading-container" style="display: none;" class="box box-primary box-solid">
        <div class="box-header">
            <h3 class="box-title">Loading data ...</h3>
        </div>
        <div style="min-height: 40px" class="box-body">
        </div>
        <div class="overlay">
            <i class="fa fa-refresh fa-spin"></i>
        </div>
    </div>
    <div id="user-liability-content">
        @if($view)
            @include($view)
        @endif
    </div>
    <div id="liab-loading-sub-container" style="display: none;" class="box box-primary box-solid">
        <div class="box-header">
            <h3 class="box-title">Loading data ...</h3>
        </div>
        <div style="min-height: 40px" class="box-body">
        </div>
        <div class="overlay">
            <i class="fa fa-refresh fa-spin"></i>
        </div>
    </div>

@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {

            var sel_type = $("#select-type").select2({
                minimumResultsForSearch: -1
            }).val('monthly').change();

            var month_input = $('#date-monthly').datepicker({
                format: "MM-yyyy",
                startView: "year",
                minViewMode: "months",
                startDate: "December-2016",
                endDate: "0d"
            });

            var day_input = $('#date-daily').datepicker({
                format: "yyyy-mm-dd",
                startDate: "2016-12-01"
            });

            var unallocated_btn = $('#liab-unallocated-btn');

            sel_type.on('change', function(e){
                if(this.value == 'daily'){
                    $('#month-group').hide();
                    unallocated_btn.hide();
                    $('#day-group').show()
                } else {
                    $('#day-group').hide();
                    $('#month-group').show();
                    if (parseFloat(unallocated_btn.data('amount')) != 0.0) {
                        unallocated_btn.show();
                    }
                }
            });

            $('.liab-search-btn').on('click', function(e) {
                e.preventDefault();
                var self = $(this);
                var action = '';
                if (self.data('action') == 'unallocated') {
                    action = 'unallocated';
                } else {
                    action = sel_type.val();
                }
                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.user-liability', ['user' => $user->id]) }}",
                    type: "POST",
                    data: {type: action, month: month_input.find('input').val(), day: day_input.find('input').val() , page: '1'},
                    beforeSend: function() {
                        unallocated_btn.fadeOut();
                        $("#user-liability-content").html('');
                        $('#liab-loading-container').show();
                    },
                    success: function (response, textStatus, jqXHR) {
                        $("#user-liability-content").html(response['html']);
                        if (response['type'] == 'daily') {
                            table_init['deferLoading'] = parseInt(response['recordsTotal']);
                            var table = $("#liability-transaction-list-datatable").DataTable(table_init);
                        } else if(response['type'] == 'monthly') {
                            $('.show-child-rows').on('click', function (e) {
                                e.preventDefault();
                                var url = "{{ $app['url_generator']->generate('accounting-liability-per-category') }}";
                                var self = $(this);
                                liabilityBreakdown(self,url);
                            });
                            if (parseFloat(response['unallocated']) != 0.0 ) {
                                unallocated_btn.data('amount', response['unallocated']);
                                unallocated_btn.fadeIn();
                            }
                        } else if(response['type'] == 'unallocated') {
                            $('.liab-unallocated-transaction-list-link').on('click', function(e) {
                                e.preventDefault();
                                setUnallocatedData(unallocated_btn, table_init, $(this));
                            });
                        }
                    },
                    complete: function() {
                        $('#liab-loading-container').hide();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-liability', ['user' => $user->id]) }}",
                "type" : "POST",
                "data": function(d){
                    d.type = 'daily';
                    d.day = day_input.find('input').val()
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };

            table_init['columns'] = [
                { "data": "date" },
                { "data": "type" , "orderable": false},
                { "data": "id" },
                { "data": "amount" },
                { "data": "balance" },
                { "data": "running_balance" },
                { "data": "difference" },
                { "data": "description" },
                { "data": "more_info" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 0, 'asc' ] ];
            table_init['lengthMenu'] = [ [25, 50, 100, 1000, 5000], [25, 50, 100, 1000, 5000] ];
            table_init['pageLength'] = 100;
            table_init['drawCallback'] = function( settings ) {
                $("#contact-list-datatable").wrap( "<div class='table-responsive'></div>" );
            };
        });

        function liabilityBreakdown(self, url) {
                if (self.hasClass("breakdown-open")) {
                    var child_row = '.' + self.data('cat') + '-child';
                    $(child_row).remove();
                    self.removeClass("breakdown-open");
                } else {
                    self.addClass("breakdown-open");
                    $.ajax({
                        url: url,
                        type: "POST",
                        data: {
                            cat: self.data('cat'),
                            currency: self.data('currency'),
                            year: self.data('year'),
                            month: self.data('month'),
                            type: self.data('type'),
                            country: self.data('country'),
                            source: self.data('source'),
                            user: self.data('user')
                        },
                        success: function (data, textStatus, jqXHR) {
                            if (data.length > 0) {
                                var parent_row = self.parent().parent();
                                $.each(data, function (item, element) {
                                    if (element['type'] == 'credit') {
                                        parent_row.after("<tr class='" + element['main_cat'] + "-child'><td>" + element['sub_cat'] + "</td><td></td><td></td><td>" + element['amount'] + "</td><td>" + element['transactions'] + "</td><td></td></tr>");
                                    } else {
                                        parent_row.after("<tr class='" + element['main_cat'] + "-child'><td>" + element['sub_cat'] + "</td><td>" + element['amount'] + "</td><td>" + element['transactions'] + "</td><td></td><td></td><td></td></tr>");
                                    }
                                });
                            } else {
                                alert("No breakdown data available under this category.")
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log(textStatus);
                            console.log(errorThrown);
                            alert('AJAX ERROR');
                        }
                    });
                }
        }

        function setUnallocatedData(unallocated_btn, table_init, self) {
            $('#date-daily').find('input').val(self.data('date'));
            $.ajax({
                url: "{{ $app['url_generator']->generate('admin.user-liability', ['user' => $user->id]) }}",
                type: "POST",
                data: {
                    type: 'daily',
                    page: '1',
                    transactions: self.data('transactions'),
                    day: self.data('date')
                },
                beforeSend: function () {
                    unallocated_btn.fadeOut();
                    $("#user-liability-sub-content").html('');
                    $('#liab-loading-sub-container').show();
                },
                success: function (response, textStatus, jqXHR) {
                    $("#user-liability-sub-content").html(response['html']);
                    if (response['type'] == 'daily') {
                        table_init['deferLoading'] = parseInt(response['recordsTotal']);
                        var table = $("#liability-transaction-list-datatable").DataTable(table_init);
                    }
                },
                complete: function () {
                    $('#liab-loading-sub-container').hide();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('AJAX ERROR');
                }
            });
        }
    </script>
@endsection
