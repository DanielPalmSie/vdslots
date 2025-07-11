@extends('admin.layout')

@section('content')

    @include('admin.promotions.races.partials.topmenu')

    <div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">Races</h3>
            <div class="float-right">
                <a href="{{ $app['url_generator']->generate('promotions.races.edit') }}"><i class="fa fa-car"></i> Add New Race</a>
            </div>
        </div>
        <div class="card-body">
            <table id="searchable-datatable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Race Type</th>
                    <th>Display as</th>
                    <th>Levels</th>
                    <th>Prizes</th>
                    <th>Game Categories</th>
                    <th>Games</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Created At</th>
                    <th>Closed</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

@endsection


@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(function () {
            var table = $("#searchable-datatable").DataTable({
                ajax : '<?php echo e($app['url_generator']->generate('promotions.races.search', [])); ?>',
                bProcessing: true,
                bServerSide: true,
                searching: false,
                iDisplayLength: 25,
                columnDefs: [{ orderable: false, targets: [0,1,2,3,4,5,6,7,8,9,10] }]
            });

        });
    </script>
@endsection

@section('header-css')
    @parent
    <link rel="stylesheet" type="text/css" href="/phive/admin/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
    <link rel="stylesheet" type="text/css" href="/phive/admin/customization/styles/css/promotions.css">
@endsection
