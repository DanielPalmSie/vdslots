@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <form id="filter-form-user-action" action="{{ $app['url_generator']->generate('admin.user-actions', ['user' => $user->id, 'by-admin' => $by_admin]) }}" method="post">
        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
        <div class="card border-top border-top-3">
            <div class="card-body">
                <div class="row">
                    @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                    @if(!empty($app['request_stack']->getCurrentRequest()->get('tag-like')))
                        <input type="hidden" name="tag-like" value="{{ $app['request_stack']->getCurrentRequest()->get('tag-like') }}">
                    @else
                        <div class="col-6 col-lg-2">
                            <div class="form-group">
                                <label for="select-tags">Tag</label>
                                <select name="tag" id="select-tags" class="form-control select2-tags" style="width: 100%;" data-placeholder="Select a tag" data-allow-clear="true">
                                    @foreach($tags as $tag)
                                        <option value="{{ $tag }}">{{ $tag }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="col-6 col-lg-2">
                        <div class="form-group">
                            <label for="select-actors">Actor</label>
                            <select name="actor" id="select-actors" class="form-control select2-actors" style="width: 100%;" data-placeholder="Select an actor" data-allow-clear="true">
                                @foreach($actors as $actor)
                                    <option value="{{ $actor->actor }}">{{ $actor->actor }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-info">Search</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body pt-0 pl-0">
            <ul class="nav nav-tabs" id="userActionsTabs" role="tablist">
                @if($by_admin && $show_admin)
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-actions', ['user' => $user->id]) }}">User Actions</a></li>
                    <li class="nav-item"><a class="nav-link active" id="adminActionsTab" data-toggle="tab" href="#admin-actions" role="tab" aria-controls="admin-actions" aria-selected="true">Admin Actions</a></li>
                @else
                    <li class="nav-item border-top border-primary"><a class="nav-link active" id="userActionsTab" data-toggle="tab" href="#user-actions" role="tab" aria-controls="user-actions" aria-selected="true">User Actions</a></li>
                    @if($show_admin)
                        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-actions', ['user' => $user->id, 'by-admin' => 1]) }}">Admin Actions</a></li>
                    @endif
                @endif
            </ul>

            <div class="tab-content mt-3 pl-2" id="userActionsTabsContent">
                <div class="tab-pane fade show active" id="user-actions" role="tabpanel" aria-labelledby="userActionsTab">
                    <table id="user-actions-datatable" class="table table-bordered table-striped" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            @if($by_admin)
                                <th>Target</th>
                            @else
                                <th>Actor</th>
                            @endif
                            <th>When</th>
                            <th>Description</th>
                            <th>Tag</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($page['data'] as $action)
                            <tr>
                                <td>{{ $by_admin ? $action->target : $action->actor }}</td>
                                <td>{{ $action->created_at }}</td>
                                <td>{!! $action->descr !!}</td>
                                <td>{{ $action->tag }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="admin-actions" role="tabpanel" aria-labelledby="adminActionsTab">
                    <!-- Admin actions content goes here -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        $(function () {
            var column = "{{$by_admin ? 'target' : 'actor'}}";
            $(".select2-actors").select2().val('{{ $app['request_stack']->getCurrentRequest()->get('actor') }}').change();
            $(".select2-tags").select2().val('{{ $app['request_stack']->getCurrentRequest()->get('tag') }}').change();

            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.user-actions', ['user' => $user->id, 'by-admin' => $by_admin]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#filter-form-user-action').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            table_init['columns'] = [
                { "data": column },
                { "data": "created_at" },
                { "data": "descr" },
                { "data": "tag" }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 1, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['lengthMenu'] = [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]];
            table_init['pageLength'] = 100;
            table_init['drawCallback'] = function( settings ) {
                $("#user-actions-datatable").wrap( "<div class='table-responsive'></div>" );
            };
            var table = $("#user-actions-datatable").DataTable(table_init);

        });
    </script>
@endsection
