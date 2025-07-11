@extends('admin.layout')

@section('content-header')

    @if ($user)
        @include('admin.user.partials.header.actions')
        @include('admin.user.partials.header.main-info')
    @endif

    @if (!$user)
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-4">
                    <h1 class="m-0">
                        {{$params['trigger_type'] == 'RG' ? 'Responsible Gaming' : 'Fraud'}} Section
                    </h1>
                </div>
                <div class="col-sm-8">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Fraud</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $params['report_title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('content')
    <div class="container-fluid">
        @if (!$user)
            @if ($params['trigger_type'] == 'RG')
                @include('admin.rg.partials.topmenu')
            @else
                @include('admin.fraud.partials.topmenu')
            @endif
        @endif

        @if ($user && $params['trigger_type'] == 'RG')
            @include('admin.rg.partials.monitoring-action-buttons')
        @endif

        @if (in_array($params['trigger_type'], ['RG', 'AML']) && !empty($user))
            {!! $app['risk_profile_rating.repository']->getView($params['trigger_type'], $user->id) !!}
        @endif

        @include('admin.fraud.partials.monitoring-filter')

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
        </style>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $params['report_title'] }}</h3>
            </div><!-- /.card-header -->
            <div class="card-body">
                <table id="obj-datatable" class="table table-striped table-bordered dt-responsive border-left w-100 border-collapse">
                    <thead>
                    <tr>
                        @foreach($columns as $k => $v)
                        <th class="col-{{ $k }} @if($k == 'color') control never @endif">{{ $v }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($page['data'] as $element)
                        <tr style="background-color: {{ $element->color ? $element->color :  '#ffffff'}};color:black;">
                            @foreach($columns as $k => $v)
                                <td class="col-{{ $k }}">{{ $element->{$k} }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div><!-- /.card-body -->
        </div>

        @if (in_array($params['trigger_type'], ['RG']) && !empty($user) && $user->country == 'GB')
            {!! \App\Repositories\BeBettorResponsibility::instance()->getView($app, 'affordability', $user->id) !!}
        @endif

        @if (in_array($params['trigger_type'], ['RG']) && !empty($user) && $user->country == 'GB')
            {!! \App\Repositories\BeBettorResponsibility::instance()->getView($app, 'vulnerability', $user->id) !!}
        @endif

        @include('admin.fraud.identity-check')
        @if(!empty($params['deposit_limit_test']))
            @include('admin.fraud.deposit-limit-test')
        @endif
    </div>
@endsection
@section('footer-javascript')
    @parent
    <script>
        var show_stats = "{{empty($user)}}";
        function preventParentBg(child) {
            $(child).parent().css("background", '#fff');
        }

        /* change font color based on lightness*/
        function color(c) {
            if(c == '' || !c)
                return '#000';
            c = c.substring(1);      // strip #
            var rgb = parseInt(c, 16);   // convert rrggbb to decimal
            var r = (rgb >> 16) & 0xff;  // extract red
            var g = (rgb >>  8) & 0xff;  // extract green
            var b = (rgb >>  0) & 0xff;  // extract blue
            var luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709
            if (luma < 100) {
                return '#fff';
            }
            return '#000';
        }
        $(function () {
            var table_init = {};
            var columns_list = JSON.parse('<?= json_encode($columns) ?>');

            var non_visible_list = ['col-color'];
            var non_visible = { "className": "none", "visible": false, "targets": []};
            non_visible.targets = non_visible_list;

            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}",
                "type" : "POST",
                "data": function(d){
                    d.start_date = "{{ $params['start_date'] }}";
                    d.end_date = "{{ $params['end_date'] }}";
                    d.form = $('#obj-datatable_filter').serializeArray();
                }
            };
            table_init['columns'] = [];
            $.each(columns_list, function(k) {
                var aux = {"data": k};
                if (k === 'declaration_proof' && show_stats) {
                    aux.render = function ( data ) {
                        return (data[0] === '1' ? '<span class="color-box blue prevent-parent-background" title="Declaration of Source of Wealth"></span>' : '')
                            + (data[1] === '1' ? '<span class="color-box red prevent-parent-background" title="Proof of Source of Wealth"></span>' : '');
                    };
                }
                table_init['columns'].push(aux);
            });
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            var username_link = {
                "targets": "col-id",
                "render": function ( data ) {
                    return '<a target="_blank" href="' + username_url + data + '/{{ \App\Helpers\URLHelper::getUrlLastSegment($app) }}">' + data + '</a>';
                }
            };
            function replacer(match, p1, p2, p3, offset, string) {
                return '<a target="_blank" href="' + username_url + p2 + '/">'+p2+'</a>';
            }
            var descr_username_link = {
                "targets": ["col-descr","col-data"],
                defaultContent: '-',
                "render": function ( data ) {
                    if(data) {
                        var res = data.split(" ");
                        var s = '';
                        for (var i = 0;i < res.length;i++) {
                           s +=  res[i].replace(/(u_)(.*)(_u)/, replacer) + ' ';
                        }
                        return s;

                    }
                }
            };

            table_init['columnDefs'] = [username_link, non_visible, descr_username_link];
            table_init['searching'] = false;
//            table_init['order'] = [ [ 0, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $("#obj-datatable").wrap( "<div class='table-responsive'></div>" );
                preventParentBg('.prevent-parent-background');
            };
            table_init['createdRow'] = function ( row, data, dataIndex ) {
                $(row).css('background-color',data.color);
                $(row).css('color',color(data.color));
            };

            table_init['order'] = [[ show_stats ? 4 : 3, "desc" ]];
            var table = $("#obj-datatable").DataTable(table_init);
        });
    </script>

@endsection
<!--@include('admin.fraud.partials.fraud-footer')-->
