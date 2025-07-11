@extends('admin.layout')

@section('header-javascript')
    @parent
@endsection

@section('content')
    @include('admin.user.partials.header.actions', compact('self_exclusion_options'))
    @include('admin.user.partials.header.main-info')
    <style>
        .chart-container {
            height: 300px;
        }
    </style>
    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">Overview</h3>
        </div><!-- /.card-header -->

        <div class="card-body">
            <!-- Filter section -->
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-calendar"></i>
                            </span>
                            </div>
                            <input type="text" class="form-control" id="graphfilter"
                                   placeholder="How many months backward?" value="{{ $app['request_stack']->getCurrentRequest()->get('months') }}">
                        </div><!-- /.input-group -->
                    </div>
                </div>
            </div><!-- /.row -->

            <!-- Charts Section -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                NGR
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-ngr" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Deposits
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-deposits" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Bets
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-bets" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
            </div><!-- /.row -->

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Gross
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-gross" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Withdrawals
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-withdrawals" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
                <div class="col-md-4">
                    <div class="card card-primary card-outline ">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Wins
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-wins" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
            </div><!-- /.row -->

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Signins
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-signins" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div><!-- /.col -->

                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Site Profit
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-site_prof" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div><!-- /.col -->

                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Rewards
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-rewards" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div><!-- /.col -->

                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar">
                                </i>
                                Weekend Booster
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="bar-chart-cashbacks" class="chart-container"></div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.card-body -->
    </div><!-- /.card -->

@endsection

<!-- FLOT CHARTS -->
@section('footer-javascript')
    @parent
    <script src="/phive/admin/customization/plugins/flot/jquery.flot.min.js"></script>
    <script src="/phive/admin/customization/plugins/flot/jquery.flot.categories.js"></script>
    <script>
        $(function () {
            var Datasets = {
                gross: {
                    data: [{!! $graphData['gross'] !!}],
                    color: "#3c8dbc",
                    label: 'Gross'
                },
                bets: {
                    data: [{!! $graphData['bets'] !!}],
                    color: "#3c8dbc",
                    label: 'Bets'
                },
                wins: {
                    data: [{!! $graphData['wins'] !!}],
                    color: "#3c8dbc",
                    label: 'Wins'
                },
                deposits: {
                    data: [{!! $graphData['deposits'] !!}],
                    color: "#3c8dbc",
                    label: 'Deposits'
                },
                withdrawals: {
                    data: [{!! $graphData['withdrawals'] !!}],
                    color: "#3c8dbc",
                    label: 'Withdrawals'
                },
                rewards: {
                    data: [{!! $graphData['rewards'] !!}],
                    color: "#3c8dbc",
                    label: 'Rewards'
                },
                site_prof: {
                    data: [{!! $graphData['site_prof'] !!}],
                    color: "#3c8dbc",
                    label: 'Site profit'
                },
                signins: {
                    data: [{!! $graphData['logins'] !!}],
                    color: "#3c8dbc",
                    label: 'Logins'
                },
                cashbacks: {
                    data: [{!! $graphData['cashbacks'] !!}],
                    color: "#3c8dbc",
                    label: 'Weekend Booster'
                },
                ngr: {
                    data: [{!! $graphData['ngr'] !!}],
                    color: "#3c8dbc",
                    label: 'NGR'
                },
            }

            Object.keys(Datasets).forEach(key => {
                var dataSet = Datasets[key];
                var chartId = `#bar-chart-${key}`;

                var graph_options = {
                    grid: {
                        borderWidth: 1,
                        borderColor: "#f3f3f3",
                        tickColor: "#f3f3f3",
                        hoverable: true
                    },
                    series: {
                        bars: {
                            show: true,
                            barWidth: 0.5,
                            align: "center"
                        }
                    },
                    xaxis: {
                        mode: "categories",
                        showTicks: false,
                        gridLines: false,
                    },
                    legend: {
                        show: false
                    },
                };

                if ($(chartId).length) {
                    $.plot(chartId, [dataSet], graph_options);
                }
            });

            $('<div id="tooltip"></div>').css({
                position: 'absolute',
                display: 'none',
                border: '1px solid #ccc',
                padding: '10px',
                'background-color': '#fff',
                'z-index': '1000',
                'border-radius': '4px',
                'box-shadow': '0 0 10px rgba(0,0,0,0.1)',
                'border-radius': '5px',
                'font-size': '13px',
            }).appendTo('body');

            $('[id^="bar-chart"]').bind('plothover', function (event, pos, item) {
                if (item) {
                    var itemLabel = item.series.label;
                    var itemData = item.series.data[item.dataIndex];

                    var tooltipContent = `${itemLabel}: <b>${itemData[1]}</b> </br> in ${itemData[0]} `;
                    $('#tooltip')
                        .html(tooltipContent)
                        .css({ top: pos.pageY + 5, left: pos.pageX + 5 })
                        .fadeIn(200);
                } else {
                    $('#tooltip').hide();
                }
            });
        });



        $('#block-user-profile-button').on('click', function () {
            alert('blocking user profile');
        });
        $('#graphfilter').on("keydown", function (event) {
            if (event.which == 13) {
                var months = $('#graphfilter').val();
                if (!(months >= 0)) {
                    alert('it should be a number');
                } else {
                    window.location.href = location.pathname + '?months=' + months;
                }
            }
        });
    </script>
@endsection
