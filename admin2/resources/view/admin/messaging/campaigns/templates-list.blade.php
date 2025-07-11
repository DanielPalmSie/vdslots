@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.messaging.partials.topmenu')

        <div class="card">
            <div class="nav-tabs-custom">
                @includeIf("admin.messaging.partials.submenu")
                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        <table id="campaign-templates-list-datatable" class="table table-striped table-bordered"
                            cellspacing="0" width="100%">
                            <thead>
                            <tr>
                                <th>Template ID</th>
                                <th>Contact List</th>
                                <th>Sent Campaigns</th>
                                <th>Bonus Template</th>
                                <th>Voucher Template</th>
                                <th>Recurring Type</th>
                                <th>Start Time</th>
                                <th>Recurring Days</th>
                                <th>Recurring End Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($recurring_list as $recurring)
                                <tr><td>{{ $recurring->id }}</td>
                                    <td>
                                        <a href='{{ $app['url_generator']->generate("messaging.contact.list-contacts", ['filter-id' => $recurring->named_search_id]) }}'>{{ $recurring->namedSearch()->first()->name  }}</a>
                                    </td>
                                    <td>{{ $recurring->campaigns()->count() }}</td>
                                    <td><a class="bonus-template-detail-link" href="#" data-bonus="{{ $recurring->bonus_template_id }}">{{ $recurring->bonusTemplate()->first()->template_name }}</a></td>
                                    <td><a class="voucher-template-detail-link" href="#" data-id="{{ $recurring->voucher_template_id }}">{{ $recurring->voucherTemplate()->first()->voucher_name }}</a></td>
                                    <td>{{ $recurring->recurring_type }}</td>
                                    <td>{{ $recurring->start_time }}</td>
                                    <td>{{ $recurring->recurring_days }}</td>
                                    <td>{{ $recurring->recurring_end_date }}</td>
                                    <td>@if(p("messaging.{$c_type->getName(true)}.campaign.new"))
                                        <a href="{{ $app['url_generator']->generate("messaging.{$c_type->getName(true)}-campaigns.new", ['action' => 'clone', 'campaign-template-id' => $recurring->id]) }}"><i
                                                    class="fa fa-clone"></i> Clone</a>
                                        @endif
                                        @if(p("messaging.{$c_type->getName(true)}.campaign.edit"))
                                        - <a href="{{ $app['url_generator']->generate("messaging.{$c_type->getName(true)}-campaigns.new", ['action' => 'edit', 'campaign-template-id' => $recurring->id]) }}"><i
                                                    class="fa fa-edit"></i> Edit</a>
                                        @endif
                                        @if(p("messaging.{$c_type->getName(true)}.campaign.delete"))
                                        - <a href="{{ $app['url_generator']->generate("messaging.campaigns.delete", ['campaign-template-id' => $recurring->id]) }}" class="href-confirm" data-message="Are your sure you want to delete the campaign?">
                                            @if($recurring->campaigns()->count() > 0)
                                                <i class="fa fa-archive"></i> Archive
                                            @else
                                                <i class="fa fa-trash"></i> Delete
                                            @endif
                                        </a>
                                        @endif
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

            $("#campaign-templates-list-datatable").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 0, "desc"]],
                "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
            });

        });
    </script>
@endsection
