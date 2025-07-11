@extends('admin.layout')

@section('content')
<div class="container-fluid">
    @include('admin.messaging.partials.topmenu')
    <div class="card">
        <div class="nav-tabs-custom">
            @includeIf("admin.messaging.partials.submenu")
            <div class="tab-content p-3">
                <div class="tab-pane active">
                    <table id="campaigns-schedules-list-datatable" class="table table-striped table-bordered"
                        cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>Scheduled Time</th>
                            <th>{{ $c_type->getName() }} Template</th>
                            <th>Contact List</th>
                            <th>Bonus</th>
                            <th>Voucher</th>
                            <th></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($future_list as $schedule)
                            <tr>
                                <td>{{ $schedule->scheduled_time }}</td>
                                <td>{{ $schedule->template->template_id }}</td>
                                <td>
                                    <a href='{{ $app['url_generator']->generate("messaging.contact.list-contacts", ['filter-id' => $schedule->template->named_search_id]) }}'>{{ $schedule->template->namedSearch()->first()->name  }}</a>
                                </td>
                                <td><a href="#" class="bonus-template-detail-link" data-bonus="{{ $schedule->template->bonus_template_id }}">{{ $schedule->template->bonusTemplate()->first()->template_name }}</a></td>
                                <td><a href="#" class="voucher-template-detail-link" data-id="{{ $schedule->template->voucher_template_id }}">{{ $schedule->template->voucherTemplate()->first()->template_name }}</a></td>
                                <td>
                                    <a href="#" class="campaign-detail-link" data-id="{{ $schedule->template->id }}"><i class="fa fa-file"></i> See details</a>
                                </td>
                                <td>
                                    <a href="{{ $app['url_generator']->generate("messaging.campaigns.delete", ['campaign-template-id' => $schedule->template->id]) }}"
                                    class="href-confirm" data-message="Are your sure you want to delete the campaign?">
                                        <i class="fa fa-archive"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div id="detail-view-modal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Detailed View</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @include('admin.partials.href-confirm')
</div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $('.bonus-template-detail-link').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.bonus.get-bonus-type-template-details') }}",
                    type: "POST",
                    data: {bonus: $(this).data('bonus')},
                    success: function (response, textStatus, jqXHR) {
                        $(".modal-body").html(response['html']);
                        $('#detail-view-modal').modal('show');
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $('.voucher-template-detail-link').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.bonus.get-voucher-template-details') }}",
                    type: "POST",
                    data: {id: $(this).data('id')},
                    success: function (response, textStatus, jqXHR) {
                        $(".modal-body").html(response['html']);
                        $('#detail-view-modal').modal('show');
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $('.campaign-detail-link').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.campaigns.get-recurring-details') }}",
                    type: "POST",
                    data: {id: $(this).data('id')},
                    success: function (response, textStatus, jqXHR) {
                        $(".modal-body").html(response['html']);
                        $('#detail-view-modal').modal('show');
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $("#campaigns-schedules-list-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 0, "asc"]],
                "columnDefs": [{"targets": 5, "orderable": false, "searchable": false}]
            });

        });
    </script>

@endsection
