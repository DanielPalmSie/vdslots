@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')

        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    @if(p('messaging.promotions.bonus-templates.list'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.bonus.list') }}">List bonus code templates</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link active">List voucher code templates</a></li>
                    @if(p('messaging.promotions.bonus-templates.new'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.bonus.create-template', ['step' => 1]) }}"><i class="fa fa-plus-square"></i> Create bonus code template</a></li>
                    @endif
                    @if(p('messaging.promotions.voucher-templates.new'))
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.vouchers.create-template') }}"><i class="fa fa-plus-square"></i> Create voucher code template</a></li>
                    @endif
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        <table id="bonus-template-list-databable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Voucher name</th>
                                <th>Voucher code</th>
                                <th>Bonus template</th>
                                <th>Reward</th>
                                <th>Count</th>
                                <th>Exclusive</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $element)
                                <tr>
                                    <td>{{ $element->id }}</td>
                                    <td>{{ $element->voucher_name }}</td>
                                    <td>{{ $element->voucher_code }}</td>
                                    <td>{{ $element->bonusTypeTemplate()->first()->bonus_name }}</td>
                                    <td>{{ $element->trophyAward()->first()->description }}</td>
                                    <td>{{ $element->count }}</td>
                                    <td>{{ $element->exclusive }}</td>
                                    <td>
                                        @if(p('messaging.promotions.voucher-templates.edit'))
                                        <a href="{{ $app['url_generator']->generate('messaging.vouchers.create-template', ['action' => 'edit', 'template-id' => $element['id']]) }}">
                                            <i class="fa fa-edit"></i> Edit</a>
                                        @endif
                                        @if(p('messaging.promotions.voucher-templates.delete'))
                                        - <a class="href-confirm" data-message="Are you sure you want to delete the Voucher code template?" href="{{ $app['url_generator']->generate('messaging.vouchers.delete-template', ['template-id' => $element['id']]) }}">
                                            <i class="fa fa-trash"></i> Delete</a>
                                        @endif
                                            <a href="#" class="detail-link" data-id="{{ $element->id }}"><i class="fa fa-eye"></i> View details</a>

                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div id="detail-view-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="detailViewModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Detailed View</h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                    </div>
                                    <div id="detail-modal-body" class="modal-body">

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
            $("#bonus-template-list-databable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 0, "desc"]],
                "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
            });

            $('#bonus-template-list-databable').on('click', '.detail-link', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.bonus.get-voucher-template-details') }}",
                    type: "POST",
                    data: {id: $(this).data('id')},
                    success: function (response, textStatus, jqXHR) {
                        $("#detail-modal-body").html(response['html']);
                        $('#detail-view-modal').modal('show');
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

        });
    </script>

@endsection
