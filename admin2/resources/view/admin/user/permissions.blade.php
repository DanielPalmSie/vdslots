@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <div class="card card-primary box-permissions border border-primary">
        <div class="card-header">
            <h3 class="card-title">User Permissions</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @if($can_show_groups)
                <div class="col-4">
                    <div class="card card-outline card-info">
                        <div class="card-header border-bottom-0">
                            <h3 class="card-title">Groups</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <tr>
                                    <th>Group</th>
                                    <th>Action</th>
                                </tr>
                                @foreach($user->groups as $group)
                                    <tr>
                                        <td>{{ $group->name }}</td>
                                        <td>
                                            @if(p('edit.permissions') || p('permission.edit.' . $group->group_id))
                                            <a class="btn btn-danger btn-xs" href="{{ $app['url_generator']->generate('admin.user-remove-group', ['user' => $user->id]) }}?group={{ $group->group_id }}">
                                                <i class="fa fa-remove"></i> Remove
                                            </a>
                                            @endif
                                            @if(p('view.user.group.permissions') || p('permission.edit.' . $group->group_id) || p('permission.view.' . $group->group_id))
                                                <a class="btn btn-primary btn-xs btn-permissions-details" href="javascript:void(0)" data-gid="{{ $group->group_id }}"
                                                   data-url="{{ $app['url_generator']->generate('admin.user-list-group-permission', ['group' => $group->group_id]) }}">
                                                    <i class="fa fa-eye"></i> Details
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        @if(p('edit.permissions') || (p('permission.edit.%') && !empty($groups)))
                        <div class="card-footer bg-white">
                            <form method="post"
                                  action="{{ $app['url_generator']->generate('admin.user-add-group', ['user' => $user->id]) }}">
                                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                <select class="form-control select2" style="width: 50%;" name="group" data-placeholder="Select a group">
                                    <option></option>
                                    @foreach($groups as $group)
                                        @if (p('edit.permissions') || p('permission.edit.' . $group->group_id))
                                        <option value="{{ $group->group_id }}">{{ $group->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button class="btn btn-info">Add new group</button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                <div id="ajax-permissions-list"></div>
                @if($permission_tags)
                <div class="col-4">
                    <div class="card card-outline card-info">
                        <div class="card-header border-bottom-0"><h3 class="card-title">Permissions</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <tr>
                                    <th>Permission</th>
                                    <th>Action</th>
                                </tr>
                                @foreach($user->permissions as $permission)
                                    <tr>
                                        <td>{{ $permission->tag }}</td>
                                        <td>
                                            <a class="btn btn-xs btn-danger"
                                               href="{{ $app['url_generator']->generate('admin.user-remove-permission', ['user' => $user->id]) }}?tag={{ $permission->tag }}">
                                                <i class="fa fa-remove"></i> Remove
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>

                        <div class="card-footer bg-white">
                            <form method="post" action="{{ $app['url_generator']->generate('admin.user-add-permission', ['user' => $user->id]) }}">
                                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                <select class="form-control select2" style="width: 50%;" name="permission" data-placeholder="Select a permission tag">
                                    <option></option>
                                    @foreach($permission_tags as $pt)
                                        <option value="{{ $pt->tag }}">{{ $pt->tag }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-info">Add new permission</button>
                            </form>
                        </div>
                    @endif
                    </div>
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
            $(".btn-permissions-details").click(function (e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: self.data('url'),
                    type: "POST",
                    data: {gid: self.data('gid')},
                    success: function (response) {
                        $("#ajax-permissions-list").html(response['html']);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        displayNotifyMessage('error', 'AJAX ERROR')
                    }
                });
            });


            $(".box-permissions .select2").select2();
        });
    </script>
@endsection
