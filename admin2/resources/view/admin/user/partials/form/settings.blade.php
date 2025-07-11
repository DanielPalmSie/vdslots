<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">User settings</h3>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form id="settings-form" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-settings-update', ['user' => $user->id]) }}"
          method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="form_id" id="form_id" value="settings">
        <div class="card-body">
            {{--<div class="form-group">--}}
                {{--<div class="col-sm-10">--}}
                    {{--<div class="checkbox">--}}
                        {{--<label><input type="checkbox" value="1" name="newsletter" {{$user->newsletter == 1 ? 'checked' : ''}}>Newsletter</label>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</div>--}}
            {{--<div class="form-group">--}}
                {{--<div class="col-sm-10">--}}
                    {{--<div class="checkbox">--}}
                        {{--<label><input type="checkbox" value="1" name="sms" {{ $settings->sms !== '0' ? 'checked' : ''}}>SMS Notifications</label>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</div>--}}
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="sms_on_login" {{ $settings->sms_on_login == 1 ? 'checked' : ''}}>
                    <label>SMS on Login</label>
                </div>
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="calls" {{ $settings->calls == 1 ? 'checked' : ''}}>
                    <label>Receive Phone Calls</label>
                </div>
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="show_in_events" {{ $settings->show_in_events == 1 ? 'checked' : ''}}>
                    <label>Show in Events Feed</label>
                </div>
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="show_notifications" {{ $settings->show_notifications == 1 ? 'checked' : ''}}>
                    <label>Show notification messages</label>
                </div>
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="realtime_updates" {{ $settings->realtime_updates == 1 ? 'checked' : ''}}>
                    <label>Realtime Race Updates</label>
                </div>
            </div>

            @if(p("users.editsegment"))
                <div class="form-group">
                    <label for="select-segment" class="col-form-label">Segment</label>
                    <select name="segment" id="select-segment" class="form-control select2-edit-segment"
                            style="width: 100%;" data-placeholder="Select a segment">
                        @foreach(\App\Helpers\DataFormatHelper::getSegments() as $key => $segment)
                            <option value="{{ $key }}">{{ $segment }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

        </div>
        <!-- /.box-body -->
        <div class="card-footer">
            @if(p('change.contact.info'))
                <button id="edit-setting-btn" value="settings" type="submit" class="btn btn-info float-right">Update user settings</button>
            @endif
        </div>
        <!-- /.box-footer -->
    </form>
</div>


@section('footer-javascript')
@parent
<script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(".select2-edit-segment").select2().select2('val', '{{ $settings->segment }}');
        $('#edit-setting-btn').click(function (e) {
            e.preventDefault();
            var dialogTitle = 'Edit Casino / Other settings';
            var dialogMessage = 'Are you sure you want to edit the user casino settings?';
            var form = $("#settings-form");
            showConfirmInForm(dialogTitle, dialogMessage, form);
        });
    });
</script>
@endsection
