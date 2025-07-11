@extends('admin.layout')
@section('content')
<div class="container-fluid">
    @include('admin.messaging.partials.topmenu')

    <div class="card">
        <div class="nav-tabs-custom">
            @includeIf("admin.messaging.partials.submenu")
            <div class="tab-content p-3">
                <div class="tab-pane active">
                    <table id="sms-templates-list-databable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Template name</th>
                            <th>Subject</th>
                            <th>Language</th>
                            <th>Created at</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($data as $element)
                            <tr>
                                <td>{{ $element->id }}</td>
                                <td>{{ $element->template_name }}</td>
                                <td>{{ $element->subject }}</td>
                                <td>{{ $element->language }}</td>
                                <td>{{ $element->created_at }}</td>
                                <td>
                                    @if(p('messaging.email.new'))
                                        <a class="duplicate-template-link text-nowrap"
                                        data-id="{{ $element->id }}" data-subject="{{ $element->subject }}"
                                        data-language="{{ $element->language }}"
                                        data-consent="{{ $element->getConsent() }}"
                                        href="{{ $app['url_generator']->generate('messaging.email-templates.duplicate', ['template' => $element->id]) }}"><i
                                                    class="fa fa-clone"></i> Clone</a> -
                                    @endif
                                    @if(p('messaging.email.edit'))
                                        <a class="text-nowrap" id="new-named-search" href="{{ $app['url_generator']->generate('messaging.email-templates.edit', ['template' => $element->id]) }}"><i class="fa fa-edit"></i> Edit</a> -
                                    @endif
                                    @if(p('messaging.email.delete'))
                                        <a class="href-confirm text-nowrap" data-message="Are you sure you want to delete the Email template?" href="{{ $app['url_generator']->generate('messaging.email-templates.delete',['template' => $element->id]) }}"><i class="fa fa-trash"></i> Delete</a> -
                                    @endif
                                    @if(p('messaging.email.campaign.new'))
                                        <a class="text-nowrap" href="{{ $app['url_generator']->generate('messaging.email-campaigns.new', ['emailTemplate' => $element->id]) }}"><i class="fa fa-calendar"></i> Schedule campaign</a> -
                                    @endif
                                    <a class="detail-link text-nowrap" href="#" data-template="{{$element->id}}"><i class="fa fa-eye"></i> View</a>

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="view-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Email details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="view-body">
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
        $(document).ready(function() {
            $("#sms-templates-list-databable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 0, "desc"]],
                "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
            });

            $('.detail-link').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.email-templates.show') }}",
                    type: "POST",
                    data: {template: $(this).data('template'), rawHtml: 1},
                    success: function (response, textStatus, jqXHR) {

                        $("#view-body").html(response['html']);
                        $('#view-modal').modal('show');
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
