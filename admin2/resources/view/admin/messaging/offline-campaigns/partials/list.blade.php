<style>
    .modal-xlg {
        width: 92%;
    }
</style>
<table id="table-datatable" class="table table-striped table-bordered" cellspacing="0" width="100%">
    <thead>
    <tr>
        <th>Id</th>
        <th>Name</th>
        <th>Contact list</th>
        <th>Type</th>
        <th>Template</th>
        <th></th>
    </tr>
    </thead>

    <tbody>
    @foreach($campaigns as $campaign)
        <tr>
            <td>{{ $campaign->id }}</td>
            <td>{{ $campaign->name }}</td>
            <td>{{ $campaign->namedSearch->name }}</td>
            <td>{{ $campaign->type }}</td>
            <td>{{  $campaign->showTemplateName() }}</td>
            <td>
                <a class="details" data-target="{{$campaign->id}}" data-export="{{empty($exports[$campaign->id])}}"><i class="fa fa-list"></i> Details</a>
                @if(p('messaging.offline-campaigns.edit') and !$exports[$campaign->id])
                    -
                    <a href="{{ $app['url_generator']->generate('messaging.offline-campaigns.edit', ['campaign' => $campaign['id']]) }}">
                        <i class="fa fa-edit"></i> Edit</a>
                @endif
                @if(p('messaging.offline-campaigns.delete') && !$exports[$campaign->id])
                    - <a class="href-confirm" data-message="Are you sure you want to delete this offline campaign?"
                         href="{{ $app['url_generator']->generate('messaging.offline-campaigns.delete', ['campaign' => $campaign['id']]) }}">
                        <i class="fa fa-trash"></i> Delete</a>
                @endif
                - {!! \App\Repositories\ExportRepository::getExportView($app, 'offline-campaigns', $campaign['id']) !!}

                @if(\App\Models\Export::lastExport($campaign['id'],'offline-campaigns',\App\Models\Export::STATUS_FINISHED))
                - {!! \App\Repositories\ExportRepository::getExportView($app, 'offline-campaigns-get-excluded', $campaign['id']) !!}
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@foreach($campaigns as $campaign)
    <div class="detailsModal-{{$campaign->id}} modal fade">
        <div class="modal-dialog  modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        {{$campaign->name}}
                        @if(intval($exports[$campaign->id]->status) > 0)
                            <span class="badge alert-success">DONE</span>
                        @else
                            <span class="badge alert-danger">FAILED</span>
                        @endif
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        @if($exports[$campaign->id]->status == 0)
                            <div class="col-12">
                                <h4>
                                    {{$exports[$campaign->id]->data}}
                                </h4>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Stats</h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            @foreach(json_decode($exports[$campaign->id]->data)->stats->fail as $reason => $count)
                                                <li class="list-group-item">
                                                    <span style="@if($count > 0) font-weight: bold @else color: grey @endif">{{  ucfirst(str_replace('_', ' ', $reason)) }}</span>
                                                    <p class="float-right">{{ $count }}</p>
                                                </li>
                                            @endforeach
                                            @if(json_decode($exports[$campaign->id]->data))
                                                <li class="list-group-item">
                                                    <span class="font-weight-bold">Sent</span>
                                                    <p class="float-right">{{ json_decode($exports[$campaign->id]->data)->count }}</p>
                                                </li>
                                            @endif
                                            @if (json_last_error() > 0)
                                                <p>{{$campaign->stats}}</p>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@endforeach

@include("admin.partials.href-confirm")

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#table-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[0, "desc"]],
                "columnDefs": [{"targets": 5, "orderable": false, "searchable": false}]
            });

        });
        $(".details").click(function () {
            if ($(this).data('export') === 1) {
                return alert('Details will be available after export.');
            }
            $(".detailsModal-" + $(this).data('target')).modal('show');
        });
    </script>
@endsection
