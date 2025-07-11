@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.settings.permission.partials.topmenu')

        <div class="card">
            <div class="card-header with-border">
                <h3 class="card-title">List of Permission Tags</h3>
                <a href="{{ $app['url_generator']->generate('settings.permissions') }}"
                class="btn btn-facebook float-right"
                id="permission_group_button"
                >Permission Groups</a>
            </div><!-- /.card-header -->
            <div class="card-body">
                <div class="card card-solid card-primary">
                    <div class="card-header with-border">
                        Actions
                    </div>
                    <div class="card-body">
                        <form action="{{$app['url_generator']->generate('permission-tag-create')}}" method="post">
                            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="text" id="tag-name" placeholder="Type permission name" class="form-control" />
                                    </div>
                                </div>
                                <div class="col-md-3" aria-haspopup="true">
                                    <button type="submit" id="add-tag-button" class="btn btn-primary" disabled="disabled"
                                            data-dtitle="Create permission tag">
                                        Create New Permission Tag
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <table class="table table-bordered permissions-table">
                    <tr>
                        <th style="width: 200px">Tag</th>
                        <th style="width: 100px">Parameter Description</th>
                        <th style="width: 100px">Action</th>
                    </tr>
                    @foreach($permission_tags as $tag)
                        <tr>
                            <td>{{ $tag->tag }}</td>
                            <td>{{ $tag->mod_desc }}</td>
                            <td role="group" aria-haspopup="true">
                                <a class="fa fa-trash action-set-btn"
                                href="{{$app['url_generator']->generate('permission-tag-delete',
                                            [
                                                    'tag_name' => $tag->tag
                                                ])}}"
                                data-dtitle="Delete permission tag"
                                data-dbody="Are you sure you want to delete permission tag <b>{{ $tag->tag}}</b>?"
                                >
                                </a>
                            </td>
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
        $('#add-tag-button').on('click', function(e) {
            e.preventDefault();

            var tag_name = $("#tag-name").val();
            var dialogTitle = $(this).data("dtitle");
            var dialogMessage = "Are you sure you want to add a new permission tag with name <b>" + tag_name + "</b>?";

            var dialogUrl = "{{$app['url_generator']->generate('permission-tag-create')}}" + "?tag_name=" + tag_name;
            if($(this).data("disabled") != 1){
                showConfirmBtn(dialogTitle, dialogMessage, dialogUrl);
            }
        });

        // Enable/disable add group button
        $("#tag-name").on('keyup', function() {
            var value = $("#tag-name").val();
            var length = value.length;
            if(length > 2) {
                $("#add-tag-button").prop('disabled', false);
            } else {
                $("#add-tag-button").prop('disabled', true);
            }
        });
    </script>
@endsection
