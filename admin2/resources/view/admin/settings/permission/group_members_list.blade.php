@extends('admin.layout')

@section('content')
    <div class="container-fluid">

    @include('admin.settings.permission.partials.topmenu')

        <div class="card">
            <div class="card-header with-border">
                <h3 class="card-title">List of Group Members: <b>{{ $group->name}}</b></h3>
                <a href="{{ $app['url_generator']->generate('settings.permissions') }}"
                class="btn btn-facebook float-right"
                id="permission_group_button"
                >Permission Groups</a>
            </div><!-- /.card-header -->
            <div class="card-body">
                @if(p('add.group.member') || p('permission.edit.' . $group->group_id))
                <div class="card card-solid card-primary">
                    <div class="card-header with-border">
                        Actions
                    </div>
                    <div class="card-body">
                        <form action="{{$app['url_generator']->generate('group-add-member')}}" method="post">
                            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="hidden" name="group_id" value="{{$group->group_id}}"/>
                                        <select name="username" id="select-username" class="form-control select2"
                                            data-placeholder="Type username" data-allow-clear="true">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="hidden" name="group_id" value="{{$group->group_id}}"/>
                                        <select name="user_id" id="select-user_id" class="form-control select2"
                                                data-placeholder="Type user id" data-allow-clear="true">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3" aria-haspopup="true">
                                    <button type="submit" id="add-member-button" class="btn btn-primary action-set-post-btn" disabled="disabled"
                                            data-dtitle="Add member">
                                        Add New Member
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 10px">User ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Country</th>
                        @if(p('remove.group.member') || p('permission.edit.' . $group->group_id))
                        <th>Action</th>
                        @endif
                    </tr>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                <a href="{{ $app['url_generator']->generate('admin.userprofile', ['user' => $user->username]) }}">{{ $user->username }}</a>

                            </td>
                            <td>{{ $user->firstname }} {{ $user->lastname }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->mobile }}</td>
                            <td>{{ $user->country }}</td>
                            @if(p('remove.group.member') || p('permission.edit.' . $group->group_id))
                            <td role="group" aria-haspopup="true">
                                <a class="fa fa-trash action-set-btn"
                                href="{{$app['url_generator']->generate('group-remove-member', [
                                                    'group_id'    => $group->group_id,
                                                    'username'    => $user->username
                                                ])}}"
                                data-dtitle="Remove member"
                                data-dbody="Are you sure you want to remove member
                                        <b>{{ $user->username }}</b> from group <b>{{ $group->name}}</b>?"
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
        $("#select-username").select2().val("");
    </script>
    <script type="text/javascript">
        $("#select-username, #select-user_id").on('change', function () {
            var disable = $("#select-username").val() === '' && $("#select-user_id").val() === '';

            if(disable) {
                $('#add-member-button').prop('disabled', true);
            } else {
                $('#add-member-button').prop('disabled', false);
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

        // Load usernames by ajax request
        $("#select-username").select2({
            ajax: {
                url: "{{$app['url_generator']->generate('group-get-usernames')}}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search_string: params.term, // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            minimumInputLength: 3
        });

        // Load usernames by ajax request
        $("#select-user_id").select2({
            ajax: {
                url: "{{$app['url_generator']->generate('group-get-user_ids')}}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search_string: params.term, // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            minimumInputLength: 3
        });

        // Show popup dialog for add button
        $('.action-set-post-btn').on('click', function(e) {
            e.preventDefault();

            var dialogTitle = $(this).data("dtitle");
            var username = $("#select-username").val();
            var user_id = $("#select-user_id").val();
            var is_id = false;

            if (username != '' && user_id != '') {
                alert('Please select either username or id not both.');
                return;
            }

            if (username == '') {
                is_id = true;
                selected_member = user_id;
            } else {
                selected_member = username;
            }

            var dialogMessage = "Are you sure you want to add member <b>" + selected_member + "</b> to group <b>{{ $group->name}}</b>?";
            var dialogUrl = "{{$app['url_generator']->generate('group-add-member', ['group_id' => $group->group_id])}}";

            if (!is_id) {
                dialogUrl += "&username=" + selected_member;
            }  else {
                dialogUrl += "&user_id=" + selected_member;
            }
            if($(this).data("disabled") != 1){
                showConfirmBtn(dialogTitle, dialogMessage, dialogUrl);
            }
        });

    </script>
@endsection
