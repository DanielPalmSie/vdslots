<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">Username on popular forums</h3>
    </div>

    <form id="edit-popular-forums-form" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-forums-update', ['user' => $user->id]) }}"
          method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="form_id" id="form_id" value="popular-forums">
        <div class="card-body">
            @foreach(\App\Helpers\DataFormatHelper::getPopularForums() as $key => $forum)
                <div class="form-group">
                    <label for="{{ 'forum-username-'. $key }}">{{ $forum }}</label>
                    <input type="text" name="{{ 'forum-username-'. $key }}" class="form-control" placeholder="" value="{{ $settings->{'forum-username-'. $key} }}">
                </div>
            @endforeach
        </div>

        <div class="card-footer">
                <button id="edit-popular-forums-btn" type="submit" class="btn btn-info float-right">Update username</button>
        </div>

    </form>
</div>

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            $('#edit-popular-forums-btn').click(function (e) {
                e.preventDefault();
                var dialogTitle = 'Edit popular forums username';
                var dialogMessage = 'Are you sure you want to edit the username on popular forums?';
                var form = $("#edit-popular-forums-form");
                showConfirmInForm(dialogTitle, dialogMessage, form);
            });
        });
    </script>
@endsection
