@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')
        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active">List bonus code templates</a></li>
                    @if(p('messaging.promotions.voucher-templates.list'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.vouchers.list') }}">List voucher code templates</a></li>
                    @endif
                    @if(p('messaging.promotions.bonus-templates.new'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.bonus.create-template', ['step' => 1]) }}"><i class="fa fa-plus-square"></i> Create bonus code template</a></li>
                    @endif
                    @if(p('messaging.promotions.voucher-templates.new'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.vouchers.create-template') }}"><i class="fa fa-plus-square"></i> Create voucher code template</a></li>
                    @endif
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        @if(p('messaging.promotions.bonus-templates.list'))
                            @include('admin.messaging.bonus.partials.template-list-table', ['show_actions' => true])
                        @else
                            <p>No permissions to see this lists.</p>
                        @endif
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
            $("#bonus-template-list-databable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 0, "desc"]],
                "columnDefs": [{"targets": 5, "orderable": false, "searchable": false}]
            });
        });
    </script>

@endsection