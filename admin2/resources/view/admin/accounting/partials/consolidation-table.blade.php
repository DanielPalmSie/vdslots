<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        <table id="consolidation-datatable-{{ $table_id }}" class="table table-striped table-bordered dt-responsive border-left"
               cellspacing="0" width="100%">
            <thead>
            <tr>
                <th style="white-space: nowrap;">Internal ID</th>
                <th>Type</th>
                <th>Status</th>
                <th>User</th>
                <th>Amount</th>
                <th>Currency</th>
                <th style="white-space: nowrap;">Time</th>
                <th>External transaction data</th>
            </tr>
            </thead>
            <tbody>
            @foreach($paginator['data'] as $element)
                <tr>
                    <td>{{ $element->internal_id }}</td>
                    <td>{{ $element->type }}</td>
                    <td>{{ $element->status }}</td>
                    <td>{{ $element->user }}</td>
                    <td>{{ $element->amount }}</td>
                    <td>{{ $element->currency }}</td>
                    <td style="white-space: nowrap;">{{ $element->trans_time }}</td>
                    <td>{{ $element->external_data }}</td>
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
            var id = "{{ $table_id }}";
            var table_id = "#consolidation-datatable-" + id;
            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('accounting-consolidation', ['user' => $user->username]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#filter-form-consolidation').serializeArray();
                    d.type = id;
                }
            };
            table_init['columns'] = [
                { "data": "internal_id" },
                { "data": "type" },
                { "data": "status"},
                {
                    "data": "user",
                    "render": function (data) {
                        return '<a target="_blank" href="' + username_url + data + '/">' + data + '</a>';
                    }
                },
                { "data": "amount"},
                { "data": "currency"},
                { "data": "trans_time"},
                {
                    "data": "external_data",
                    "class": "cell-no-padding",
                    "sortable": "false",
                    "render": function (data) {
                        if (data == '') {
                            return '<p class="empty-cell-text">NO DATA</p>';
                        }
                        var converted_data = JSON.parse(data);
                        var header = '<thead><tr>';
                        var row = '<tbody><tr>';
                        $.each(converted_data, function (key, val) {
                            header += '<th>' + key + '</th>';
                            row += '<td style="white-space: nowrap;">' + val + '</td>';
                        });
                        header += '</tr></thead>';
                        row += '</tr></tbody>';
                        return '<table class="table table-condensed sub-table">' + header + row + '</table>';
                    }
                }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 6, 'desc' ] ];
            /*table_init['responsive'] = { "details" : { "type": 'column' } };*/
            table_init['deferLoading'] = parseInt("{{ $paginator['recordsTotal'] }}");
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['pageLength'] = 25;
            table_init['drawCallback'] = function( settings ) {
                $(table_id).wrap( "<div class='table-responsive'></div>" );
            };

            var table = $(table_id).DataTable(table_init);

        });
    </script>
@endsection
