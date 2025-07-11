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
                                <th>Language</th>
                                <th>Template</th>
                                <th>Created at</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $element)
                                <tr>
                                    <td>{{ $element->id }}</td>
                                    <td>{{ $element->template_name }}</td>
                                    <td>{{ $element->language }}</td>
                                    <td>{{ $element->template }}</td>
                                    <td>{{ $element->created_at }}</td>
                                    <td>
                                        @if(p('messaging.sms.edit'))
                                            <a class="text-nowrap" id="new-named-search" href="{{ $app['url_generator']->generate('messaging.sms-templates.clone', ['smsTemplate' => $element->id]) }}"><i class="fa fa-clone"></i> Clone</a> -
                                        @endif
                                        @if(p('messaging.sms.edit'))
                                            <a class="text-nowrap" id="new-named-search" href="{{ $app['url_generator']->generate('messaging.sms-templates.edit', ['smsTemplate' => $element->id]) }}"><i class="fa fa-edit"></i> Edit</a> -
                                        @endif
                                        @if(p('messaging.sms.delete'))
                                            <a id="new-named-search" class="href-confirm text-nowrap" data-message="Are your sure you want to delete the SMS template?" href="{{ $app['url_generator']->generate('messaging.sms-templates.delete', ['smsTemplate' => $element->id]) }}"><i class="fa fa-trash"></i> Delete</a> -
                                        @endif
                                        @if(p('messaging.sms.campaign.new'))
                                            <a class="text-nowrap" id="new-named-search" href="{{ $app['url_generator']->generate('messaging.sms-campaigns.new', ['smsTemplate' => $element->id]) }}"><i class="fa fa-calendar"></i> Schedule campaign</a>
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

        @include('admin.partials.href-confirm')

    </div>

@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#sms-templates-list-databable").DataTable({
                "pageLength": 25,
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
