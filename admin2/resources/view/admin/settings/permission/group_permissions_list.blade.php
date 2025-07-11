@extends('admin.layout')

@section('content')
    <div class="container-fluid">

    @include('admin.settings.permission.partials.topmenu')

        <div class="card">
            <div class="card-header with-border">
                <h3 class="card-title">List of Group Permissions: <b>{{ $group->name}}</b></h3>
                <a href="{{ $app['url_generator']->generate('settings.permissions') }}"
                class="btn btn-facebook float-right"
                id="permission_group_button"
                >Permission Groups</a>
            </div><!-- /.card-header -->
            <div class="card-body">
                @if(p('add.group.permissions'))
                <div class="card card-solid card-primary">
                    <div class="card-header with-border">
                        Actions
                    </div>
                    <div class="card-body">
                        <form action="{{$app['url_generator']->generate('group-add-permissions')}}" method="post">
                            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <select name="tag" id="select-tag" class="form-control select2"
                                            data-placeholder="Select permission" data-allow-clear="true">
                                            <option></option>
                                            @foreach($all_permission_tags as $ptag)
                                                <option value="{{ $ptag->tag}}">{{ $ptag->tag }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3" aria-haspopup="true">
                                    <input type="hidden" name="group_id" value="{{$group->group_id}}" />
                                    <button type="submit" id="add-permission-button" class="btn btn-primary action-set-post-btn" disabled="disabled"
                                            data-dtitle="Add permission"
                                            data-dbody="Are you sure you want to add permission <b>{{ $permission->tag }}</b> to group <b>{{ $group->name}}</b>?">
                                        Add Permission
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="text" id="group-name" value="{{ $group->name}}" placeholder="Edit group name" class="form-control" />
                                    </div>
                                </div>
                                <div class="col-md-3" aria-haspopup="true">
                                    <input type="hidden" name="group_id" value="{{$group->group_id}}" />
                                    <button type="submit" id="rename-group-button" class="btn btn-primary action-set-rename-btn" disabled="disabled"
                                            data-dtitle="Rename group">
                                        Rename Group
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                <table class="table table-bordered permissions-table">
                    <tr>
                        <th>Tag</th>
                        <th>Modifier</th>
                        <th>Permission</th>
                        @if(p('remove.group.permissions'))
                        <th>Action</th>
                        @endif
                    </tr>
                    @foreach($current_permissions as $permission)
                        <tr>
                            <td>{{ $permission->tag }}</td>
                            <td>{{ $permission->mod_value }}</td>
                            <td>{{ $permission->permission }}</td>
                            @if(p('remove.group.permissions'))
                            <td role="group" aria-haspopup="true">
                                <a class="fa fa-trash action-set-btn"
                                href="{{$app['url_generator']->generate('group-remove-permission-from-group',
                                            [
                                                    'group' => $group->group_id,
                                                    'permission_tag' => $permission->tag,
                                                    'mod_value' => $permission->mod_value
                                                ])}}"
                                data-dtitle="Remove permission"
                                data-dbody="Are you sure you want to remove permission
                                        <b>{{ $permission->tag }}</b> from group <b>{{ $group->name}}</b>?"
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
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        $("#select-tag").select2().val("");
    </script>
    <script type="text/javascript">

        $("#select-tag").on('change', function() {
            if($("#select-tag").val() === '') {
                $('#add-permission-button').prop('disabled', true);
            } else {
                $('#add-permission-button').prop('disabled', false);
            }
        });

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

        // Show popup dialog for add button
        $('.action-set-post-btn').on('click', function(e) {
            e.preventDefault();

            var dialogTitle = $(this).data("dtitle");
            var selected_permission = $("#select-tag").val();
            var dialogMessage = "Are you sure you want to add permission <b>"
                    + selected_permission + "</b> to group <b>{{ $group->name}}</b>?";
            var dialogUrl = "{{$app['url_generator']->generate('group-add-permissions', ['group_id' => $group->group_id])}}"
                    + "&permission_tag=" + selected_permission;
            if($(this).data("disabled") != 1){
                showConfirmBtn(dialogTitle, dialogMessage, dialogUrl);
            }
        });

        $("#group-name").on('keyup', function() {
            var new_name = $("#group-name").val();
            var old_name = "{{ $group->name}}";
            if(new_name === old_name) {
                $('#rename-group-button').prop('disabled', true);
            } else {
                $('#rename-group-button').prop('disabled', false);
            }
        });

        // Show popup dialog for rename button
        $('.action-set-rename-btn').on('click', function(e) {
            e.preventDefault();

            var dialogTitle = $(this).data("dtitle");
            var new_name = $("#group-name").val();
            var dialogMessage = "Are you sure you want rename group <b>{{$group->name}}</b> to <b>" + new_name + "</b>?";
            var dialogUrl = "{{$app['url_generator']->generate('group-edit-group', ['group_id' => $group->group_id])}}"
                    + "&new_name=" + new_name;
            if($(this).data("disabled") != 1){
                showConfirmBtn(dialogTitle, dialogMessage, dialogUrl);
            }
        });
    </script>
@endsection
