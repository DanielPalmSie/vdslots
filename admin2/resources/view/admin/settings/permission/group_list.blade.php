@extends('admin.layout')

@section('content')
    <div class="container-fluid">

    @include('admin.settings.permission.partials.topmenu')

        <div class="card">
            <div class="card-header with-border">
                <h3 class="card-title">List of Permission Groups</h3>
                <a href="{{ $app['url_generator']->generate('permissions.tag-list') }}"
                class="btn btn-facebook float-right"
                id="permission-tag-button"
                >Permission Tags</a>
            </div><!-- /.card-header -->
            <div class="card-body">
                @if(p('create.groups'))
                <div class="card card-solid card-primary">
                    <div class="card-header with-border">
                        Actions
                    </div>
                    <div class="card-body">
                        <form action="{{$app['url_generator']->generate('group-add-group')}}" method="post">
                        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="text" id="group-name" placeholder="Type group name" class="form-control" />
                                    </div>
                                </div>
                                <div class="col-md-3" aria-haspopup="true">
                                    <button type="submit" id="add-group-button" class="btn btn-primary" disabled="disabled"
                                            data-dtitle="Add group">
                                        Add New Group
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                <table class="table table-bordered permissions-table">
                    <tr>
                        <th style="width: 72px">Group Id</th>
                        <th style="width: 350px">Group Name</th>
                        <th style="width: 100px">Members</th>
                        <th style="width: 100px">Permissions</th>
                        @if(p('delete.permission.group'))
                        <th style="width: 100px">Action</th>
                        @endif
                    </tr>
                    @foreach($groups as $group)
                        <tr>
                            <td style="text-align: center">{{ $group->group_id }}</td>
                            <td>{{ $group->name }}</td>
                            <td>
                                <a href="{{ $app['url_generator']->generate('group-members', ['group' => $group->group_id]) }}">
                                    <span class="badge bg-blue">{{ $group->groupMembers()->count() }}</span>
                                </a>
                            </td>
                            <td>
                                <a href="{{ $app['url_generator']->generate('group-permissions', ['group' => $group->group_id]) }}">
                                    <span class="badge bg-yellow">{{ $group->permission_groups()->count() }}</span>
                                </a>
                            </td>
                            @if(p('delete.permission.group'))
                            <td role="group" aria-haspopup="true">
                                <a class="fa fa-trash action-set-btn"
                                href="{{$app['url_generator']->generate('group-delete-group',
                                            [
                                                    'group' => $group->group_id
                                                ])}}"
                                data-dtitle="Delete group"
                                data-dbody="Are you sure you want to delete permission group <b>{{ $group->name}}</b>?"
                                >
                                </a>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div><!-- /.card-body -->
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">

        // Show popup dialog for delete button
        $('.action-set-btn').on('click', function(e) {
            e.preventDefault();

            var dialogTitle = $(this).data("dtitle");
            var dialogMessage = $(this).data("dbody");
            var dialogUrl = $(this).attr('href');
            if($(this).data("disabled") != 1){
                showConfirmBtn(dialogTitle, dialogMessage, dialogUrl);
            }
        });

        // Show popup for add group button
        $('#add-group-button').on('click', function(e) {
            e.preventDefault();

            var group_name = $("#group-name").val();
            var dialogTitle = $(this).data("dtitle");
            var dialogMessage = "Are you sure you want to add a new group with name <b>" + group_name + "</b>?";

            var dialogUrl = "{{$app['url_generator']->generate('group-add-group')}}" + "?group_name=" + group_name;
            if($(this).data("disabled") != 1){
                showConfirmBtn(dialogTitle, dialogMessage, dialogUrl);
            }
        });

        // Enable/disable add group button
        $("#group-name").on('keyup', function() {
            var value = $("#group-name").val();
            var length = value.length;
            if(length > 2) {
                $("#add-group-button").prop('disabled', false);
            } else {
                $("#add-group-button").prop('disabled', true);
            }
        });
    </script>
@endsection
