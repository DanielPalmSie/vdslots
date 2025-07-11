<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">Privacy settings</h3>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form id="user_privacy_settings" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-privacy-settings-update', ['user' => $user->id]) }}"
          method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <div class="card-body">
            @if (p("users.editall"))
                @foreach ($user->getPrivacySettings() as $key => $setting)
                    <div class="form-group">
                        <div class="col-sm-10">
                            <div class="form-check checkbox">
                                <input class="target-checkbox form-check-input" type="checkbox" value="1"
                                       name="settings[{{$key}}]" {{ !empty($setting['value']) ? 'checked' : ''}}>
                                <label class="form-check-label">
                                    {{$setting['label']}}
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <!-- /.box-body -->
        <div class="card-footer">
            @if(p('change.contact.info'))
                <input type="button" value="Enable all" class="btn btn-warning enable-all">
                <input type="button" value="Disable all" class="btn btn-danger disable-all">
                <button type="submit" class="btn btn-info float-right" id="edit-privacy-settings">Update privacy settings</button>
            @endif
        </div>
        <!-- /.box-footer -->
    </form>
</div>


@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            $("#user_privacy_settings .enable-all").click(function() {
                $("#user_privacy_settings .target-checkbox").prop('checked', true);
            });
            $("#user_privacy_settings .disable-all").click(function() {
                $("#user_privacy_settings .target-checkbox").prop('checked', false);
            });
            $('#edit-privacy-settings').click(function (e) {
                e.preventDefault();
                var dialogTitle = 'Edit Casino / Other settings';
                var dialogMessage = 'Are you sure you want to edit privacy settings for this user?';
                var form = $("#user_privacy_settings");
                showConfirmInForm(dialogTitle, dialogMessage, form);
            });
        });
    </script>
@endsection
