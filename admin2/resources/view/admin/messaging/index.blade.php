@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')

        <div class="row mb-5">
            <div class="col-6">
                <div class="input-group">
                    <button type="button" class="btn btn-default btn-flat float-right" data-target="#dashboard-stats-box"
                            id="dashboard-daterange-btn" data-url="{{ $app['url_generator']->generate('messaging.get-dashboard-stats') }}">
                        <i class="fa fa-calendar"></i> <span>This month</span> <i class="fa fa-caret-down"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row" id="dashboard-stats-box">
            @include('admin.messaging.partials.dashboard-stats')

            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fa fa-users"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">Total active contacts</span>
                        <span class="info-box-number">{{ number_format($data['active_contacts']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fa fa-filter"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">Total filtered contacts</span>
                        <span class="info-box-number">{{ number_format($data['filtered_contacts']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Latest Campaigns</h3>
                    </div>
                    <div class="card-body">
                        <div class="#dashboard-campaigns-list">
                            @include('admin.messaging.campaigns.partials.past-list')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            var selector = $('#dashboard-daterange-btn');
            selector.daterangepicker(
                    {
                        linkedCalendars: false,
                        opens: "right",
                        alwaysShowCalendars: true,
                        autoUpdateInput: false,
                        locale: {
                            format: 'YYYY-MM-DD',
                            firstDay: 1,
                            cancelLabel: 'All Time'
                        },
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    },
                    function (start, end) {
                        selector.find('span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        $.ajax({
                            url: selector.data('url'),
                            type: "POST",
                            data: {start_date: start.format('YYYY-MM-DD'), end_date: end.format('YYYY-MM-DD')},
                            success: function (response) {
                                $(selector.data('target')).html(response['html']);
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                alert('AJAX ERROR');
                            }
                        });
                    }
            );
            selector.on('cancel.daterangepicker', function(ev, picker) {
                selector.find('span').html('All Time');
                $.ajax({
                    url: selector.data('url'),
                    type: "POST",
                    data: {start_date: null, end_date: null},
                    success: function (response) {
                        $(selector.data('target')).html(response['html']);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });
        });
    </script>
@endsection
