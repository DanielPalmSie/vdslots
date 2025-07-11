<style>
    #change-log-table td:nth-child(3),
    #change-log-table th:nth-child(3) {
        word-break: break-all;
    }
</style>
<div class="card card-solid card-primary">
    <div class="card-header">
        <h3 class="card-title">Changes log</h3>
    </div>

    <form role="form" id="changes_log_form">
        <input type="hidden" name="id" value="{{$game_id}}">

        <div class="card-body">
            <table id="change-log-table" class="table table-striped table-bordered" cellspacing="0" width="100%" style="max-width: 100%;overflow: scroll;">
                <thead>
                <tr>
                    @foreach($columns as $key => $column)
                        <th>{{$column}}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>
    </form>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {

            var table = $("#change-log-table").DataTable({
                'processing': true,
                'serverSide': true,
                'searching': false,
                'orderMulti': true,
                'deferLoading': parseInt("{{ $paginator['recordsTotal'] }}"),
                'pageLength': '{{$request->get('report-table_length', 25)}}',
                'order': [1, 'desc'],
                'ajax': {
                    "url" : "{{ $app['url_generator']->generate('settings.games.changes-log') }}",
                    "type" : "POST",
                    "data": function(d){
                        d.form = $('#changes_log_form').serializeArray();
                    }
                },
                'columns': ({!! json_encode(array_keys($columns)) !!}).map(function(column) {
                    return  {"data": column}
                })
            });

            $(document).ready(function() {
                table.ajax.reload();
            })
        });
    </script>

@endsection
