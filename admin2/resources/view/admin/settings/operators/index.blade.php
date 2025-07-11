@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.settings.games.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Operators</h3>
                <div class="float-right">
                    <a href="{{ $app['url_generator']->generate('settings.operators.edit') }}"><i class="fa fa-gamepad"></i> Add New Operator</a>
                </div>
            </div>
            <div class="card-body">
                <table id="searchable-datatable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Operator</th>
                        <th>Network</th>
                        <th>Operation Fees (Branded, Non Branded)</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(function () {
            $("#searchable-datatable").DataTable({
                'processing': true,
                'serverSide': true,
                'ajax': {
                    "url": "{{ $app['url_generator']->generate('settings.operators.search', []) }}",
                    "type": "GET"
                },
                'order': [[0, 'desc']],
                'pageLength': 25,
                'columns': [
                    {"data": "name"},
                    {"data": "network"},
                    {"data": "fees"}
                ]
            });

        });
    </script>

@endsection
