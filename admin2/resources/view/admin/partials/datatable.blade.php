<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{$title}}</h3>
        <a class="float-right" id="export-download-link" href="#"><i class="fa fa-download"></i> Download</a>
    </div>
    <div class="card-body">
        <table id="obj-datatable" class="table table-striped table-bordered dt-responsive w-100 border-collapse border-left">
            <thead>
                <tr>
                    @foreach($columns as $k => $v)
                    <th class="col-{{ $k }}" >{{ $v }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($page['data'] as $obj)
                <tr>
                    @foreach($columns as $name => $display_name)
                    <td class="col-{{ $name }}">{{ $obj->{$name} }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@section('footer-javascript')
    @parent
    <script>
        $(function () {
            var table_init = {};
            var columns_list = JSON.parse('<?= json_encode($columns) ?>');
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}",
                "type" : "POST",
                "data": function(d){
//                    d.start_date = "{{ $params['start_date'] }}";
//                    d.end_date = "{{ $params['end_date'] }}";
                    d.form = $('#obj-datatable_filter').serializeArray();
                }
            };
            table_init['columns'] = [];
            $.each(columns_list, function(k, v) {
                table_init['columns'].push({ "data": k});
            });
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };

            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            var username_section = "{{ $user_section }}";
            var username_link = {
                "targets": "col-id",
                "render": function ( data ) {
                    return '<a target="_blank" href="' + username_url + data + '/' + username_section + '">' + data + '</a>';
                }
            };

            table_init['columnDefs'] = [username_link];
            table_init['searching'] = false;
            table_init['order'] = [ [ <?= $sort ? $sort : 0 ?>, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = parseInt("{{ $length }}");
            table_init['drawCallback'] = function( settings ) {
                $("#obj-datatable").wrap( "<div class='table-responsive'></div>" );
            };

            var table = $("#obj-datatable").DataTable(table_init);

            $('#export-download-link').on( 'click', function (e) {
                e.preventDefault();
                var form = $('#obj-datatable_filter');
                form.append('<input type="hidden" name="export" value="1" /> ');
                form.attr('target', '_blank');
                form.submit();
                form.removeAttr('target');
                form.find('input[name=export]').remove();
            });
        });
    </script>

@endsection
