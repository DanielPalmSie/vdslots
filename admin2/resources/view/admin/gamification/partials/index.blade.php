@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>{{ $view['title'] }}</h1>
            </div>


            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('gamification-dashboard') }}">Gamification</a></li>
                    <li class="breadcrumb-item active">{{ $breadcrumb }}</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.partials.topmenu')

        @if($view['variable'] != 'tournaments')
        <p><a href="{{ $app['url_generator']->generate($view['create_route'] ?? $view['variable'] . '.new') }}"><i class="fa fa-shield-alt"></i> Create a New {{ $view['new'] }}</a></p>
        @endif

        <div class="card card-solid card-primary">
            @if($view['variable'] != 'tournaments')
                <div class="card-header">
                    <h3 class="card-title">{{ $view['new'] }}</h3>
                    <div style="float: right">
                        <a href="{{ $app['url_generator']->generate($view['create_route'] ?? $view['variable'] . '.new') }}">
                            <i class="fa fa-shield-alt"></i>
                            Create a New {{ $view['new'] }}
                        </a>
                    </div>
                </div>
            @endif
            <div class="card-body">
                <div id="toggle-menu" style="margin-bottom: 8px;">
                @foreach($columns['list'] as $k => $v)
                    @if($k != '')
                        <button style="margin-bottom: 3px" id="toggle-btn-{{ $k }}"
                            @if(in_array("col-$k", $columns['no_visible'])) class="btn btn-sm btn-default toggle-column-btn" @else class="btn btn-sm btn-warning toggle-column-btn" @endif
                            data-column="col-{{ $k }}">{{ $v }}</button>
                    @endif
                @endforeach
                </div>
                <div style="margin-bottom: 10px;"><a href="#" id="show-toggle-menu"><i class="far fa-caret-square-up"></i> Toggle controls</a></div>
                <table id="{{ $view['variable'] }}-searchable-datatable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        @foreach($columns['list'] as $k => $v)
                            <th @if(in_array("col-$k", $columns['no_visible'])) style="display: none" @endif class="col-{{ $k }}">{{ $v }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($pagination['data'] as $element)
                        <tr>
                            @foreach($columns['list'] as $k => $v)
                                <td @if(in_array("col-$k", $columns['no_visible'])) style="display: none" @endif class="col-{{ $k }}">{{ $element->{$k} }}</td>
                            @endforeach
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

    <script type="text/javascript">

        var id_link_default = {
            "targets": "col-id",
            "render": function ( data ) {
                var edit_link = "{{ $app['url_generator']->generate($view['edit_route'] ?? $view['variable'] . '.edit', [$view['variable_param'] => -1]) }}";
                edit_link = edit_link.replace("-1", data);

                return '<a href="'+edit_link+'"><i class="far fa-edit"></i>&nbsp;&nbsp;'+data+'</a>&nbsp;';
            }
        };

        //$(function() {
        $(document).ready(function() {

            $('#show-toggle-menu').click(function() {
                $('#toggle-menu').slideToggle("slow");
                $("i",this).toggleClass("fa-caret-square-down fa-caret-square-up");
            });

            var non_visible_list = JSON.parse(Cookies.get("{{ $view['variable'] }}-search-no-visible"));
            var columns_list = JSON.parse('<?= json_encode($columns['list']) ?>');

            var non_visible = { "className": "none", "visible": false, "targets": []};
            non_visible.targets = non_visible_list;

            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate($view['variable'].'.search', []) }}",
                "type" : "POST"
            };

            table_init['columns'] = [];
            $.each(columns_list, function(k,v) {
                table_init['columns'].push({ "data": k});
            });
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };

            table_init['searching'] = true;
            table_init['order'] = [ [ 1, 'asc' ] ];
            table_init['deferLoading'] = parseInt("{{ $pagination['defer_option'] }}");
            table_init['deferRender'] = true;
            table_init['pageLength'] = parseInt("{{ $pagination['initial_length'] }}");

            var id_link = id_link_default;
            if (typeof getIDLink == "function") {
                id_link = getIDLink();
            }

            var active = {
                "targets": "col-active",
                "render": function ( data ) {
                    if (data == 1){
                        return 'Yes';
                    } else {
                        return 'No';
                    }
                }
            };

            table_init['columnDefs'] = [non_visible, id_link, active];

            var toFocusOn = null;
            table_init['drawCallback'] = function( settings ) {
                if (toFocusOn) {
                    // TODO: This is probably not the best way, but will do for now.
                    setTimeout(function() {
                        if (toFocusOn) {
                            toFocusOn.focus();
                            toFocusOn = null;
                        }
                    }, 1);
                } else {
                    $("#{{ $view['variable'] }}-searchable-datatable").wrap("<div class='table-responsive'></div>");
                }
            }

            $('#{{ $view['variable'] }}-searchable-datatable thead th').each(function(){
                var title = $(this).text();
                $(this).html('<div class=""><b>'+title+'</b><br /><input class="form-control input-sm" type="text" placeholder="Search '+title+'" /></div>');
            });

            var table = $("#{{ $view['variable'] }}-searchable-datatable").DataTable(table_init);

            // Apply the search
            table.columns().eq(0).each(function(colIdx) {
                $('input', table.column(colIdx).header()).on('keyup change', function (e) {
                    toFocusOn = $(this);
                    table
                        .column(colIdx)
                        .search(this.value)
                        .draw()
                        ;
                        /*
                    setTimeout(function(){
                        toFocusOn.focus();
                    }, 100);
                    */
                });

                $('input', table.column(colIdx).header()).on('click', function(e) {
                    e.stopPropagation();
                });
            });


            $('.toggle-column-btn').on( 'click', function (e) {
                e.preventDefault();

                var from_cookie = JSON.parse(Cookies.get("{{ $view['variable'] }}-search-no-visible"));
                var self = $(this);
                var selector = "." + self.attr('data-column');
                var column = table.column(selector);

                if ($.inArray(self.attr('data-column'), from_cookie) == -1) {
                    from_cookie.push(self.attr('data-column'));
                } else {
                    from_cookie = jQuery.grep(from_cookie, function(value) {
                        return value != self.attr('data-column');
                    });
                }

                var json_cookie = JSON.stringify(from_cookie);
                Cookies.get("{{ $view['variable'] }}-search-no-visible", json_cookie);

                column.visible( ! column.visible() );

                if (column.visible()) {
                    self.removeClass("btn-default").addClass("btn-warning");
                } else {
                    self.removeClass("btn-warning").addClass("btn-default");
                }

                if ($(selector).css("display") == 'none') {
                    $(selector).css("display", '');
                }
            });
        });
    </script>

    @stack('footer-javascript-addition')

@endsection
