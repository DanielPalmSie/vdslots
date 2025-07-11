@extends('admin.layout')

@section('content')
        <style>
            div.dataTables_processing {
                z-index: 1;
            }
            .btn.selected {
                color: #ffffff;
                background: #3c8dbce2;
            }
        </style>
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Users list</h3>
                @php
                    $download_config_status = !empty($app['vs.config']['active.sections']['user.download']) && $app['vs.config']['active.sections']['user.download'] ? true : false;
                @endphp
                @if(p('download.csv') && $download_config_status)
                    <a class="float-right" id="user-download-link" href="#"><i class="fa fa-download"></i> Download CSV</a>
                @endif
            </div>
            <div class="card-body border border-primary">
                <div id="toggle-menu-id" class="mb-2 collapse">
                    @foreach($columns['list'] as $k => $v)
                        @if($k != 'id')
                            <button class="btn btn-sm btn-outline-secondary mb-1 toggle-column-btn" id="toggle-btn-{{ $k }}" data-column="col-{{ $k }}">{{ htmlspecialchars($v) }}</button>
                        @endif
                    @endforeach
                </div>
                <div class="mb-2">
                    <a href="#" id="show-toggle-menu" class="text-lightblue" data-toggle="collapse" data-target="#toggle-menu-id">
                        <i class="fas fa-caret-square-up"></i> Toggle controls
                    </a>
                </div>
                <div class="table-responsive">
                    <table id="user-search-list-datatable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            @foreach($columns['list'] as $k => $v)
                                <th @if(!in_array("col-$k", $columns['visible'])) style="display: none" @endif class="col-{{ $k }}">{{ htmlspecialchars($v) }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($initial['data'] as $element)
                            <tr>
                                @foreach($columns['list'] as $k => $v)
                                    <td @if(!in_array("col-$k", $columns['visible'])) style="display: none" @endif class="col-{{ $k }}">{{ $element->{$k} }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @include('admin.user.partials.user-search-filter')
@endsection


@section('footer-javascript')
    @parent
    <script>
        $(function () {
            if ($('[name="user[username]"]').val() === '') {
                var quick_string = $('[name="user[id]"]').val();
                if (!$.isNumeric(quick_string)){
                    $('[name="user[id]"]').val('');
                    $('[name="user[username]"]').val(quick_string);
                }
            }

            var visible_list = JSON.parse(Cookies.get("user-search-visible"));
            var columns_list = JSON.parse('<?= json_encode($columns['list']) ?>');
            var non_visible_list = Object.keys(columns_list).map(col => 'col-' + col);
            non_visible_list = non_visible_list.filter(key => !visible_list.includes(key));

            var non_visible = { "className": "none", "visible": false, "targets": []};
            non_visible.targets = non_visible_list;

            visible_list.forEach(function(column) {
                $('.toggle-column-btn[data-column="' + column + '"]').addClass('selected');
            });
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('user-ajax') }}",
                "type" : "POST",
                "data": function(d){
                    var array = $('#search-form-34').serializeArray();
                    var result = [];

                    $.each( array, function( index, value ) {
                        // need to skip the parsing of the token (it broke on the split below)
                        if(value.name == 'token') {
                            return;
                        }

                        var name_array = value.name.split('[');

                        if (name_array.length === 1) {
                            if (value.hasOwnProperty('name') && value.hasOwnProperty('value')) {
                                var obj = {};
                                obj[value.name] = value.value;
                                result.push(obj);
                                return;
                            }

                            result.push(value);
                            return;
                        }

                        var nkey = name_array[0];
                        var nname = name_array[1].slice(0, -1);
                        var nvalue = value.value;
                        var obj = {};
                        var obj2 = {};
                        obj2[nname] = nvalue;
                        obj[nkey] = obj2;
                        result.push(obj);
                    });
                    d.form = result;
                }
            };
            table_init['columns'] = [];
            $.each(columns_list, function(k,v) {
                table_init['columns'].push({ "data": k, "defaultContent": "N/A"});
            });
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };

            table_init['searching'] = false;
            table_init['order'] = [ [ 0, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $initial['defer_option'] }}");
            table_init['deferRender'] = true;
            table_init['pageLength'] = parseInt("{{ $initial['initial_length'] }}");

            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";

            var username_link = {
                "targets": "col-id",
                "render": function ( data ) {
                    return '<a target="_blank" href="' + username_url + data + '/">' + data + '</a>';
                }
            };

            var backend_link = {
                "targets": "col-backend",
                "render": function ( data ) {
                    return '<a target="_blank" href="/account/' + data + '">Go to profile</a>';
                }
            };

            var playcheck_link = {
                "targets": "col-playcheck",
                "render": function ( data ) {
                    return '<a target="_blank" href="/phive/modules/Micro/playcheck.php?uid=/' + data + '">PlayCheck</a>';
                }
            };

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

            table_init['columnDefs'] = [non_visible, username_link, backend_link, playcheck_link, active];
            var table = $("#user-search-list-datatable").DataTable(table_init);

            $('.toggle-column-btn').on( 'click', function (e) {
                e.preventDefault();

                var from_cookie = JSON.parse(Cookies.get("user-search-visible"));
                var self = $(this);
                var selector = "." + self.attr('data-column');
                var column = table.column(selector);

                var should_ajax_reload = false;
                if ($.inArray(self.attr('data-column'), from_cookie) == -1) {
                    from_cookie.push(self.attr('data-column'));
                    should_ajax_reload = true;
                } else {
                    from_cookie = jQuery.grep(from_cookie, function(value) {
                        return value != self.attr('data-column');
                    });
                }
                var json_cookie = JSON.stringify(from_cookie)
                Cookies.remove("user-search-visible");
                Cookies.set("user-search-visible", json_cookie, { path: '/'});


                if(should_ajax_reload) {
                    table.ajax.reload(function() {
                        column.visible(true);
                        $(selector).css("display", '');
                        self.toggleClass('selected');
                    });
                }else {
                    column.visible(false);
                    $(selector).css("display", '');
                    self.toggleClass('selected');
                }
            });

            $('#user-download-link').on( 'click', function (e) {
                e.preventDefault();
                var form = $('#search-form-34');
                form.attr('action', "{{ $app['url_generator']->generate('user.search.export') }}");
                form.submit();
            });

            $('#show-toggle-menu').click(function() {
                $('#toggle-menu').slideToggle("slow");
                $("i",this).toggleClass("fa-caret-square-up fa-caret-square-down");
            });
        });
    </script>
@endsection
