<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">Enabled Deposit Methods</h3>
    </div>

    <form id="deposits-methods" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-deposits-methods-update', ['user' => $user->id]) }}"
          method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="form_id" id="form_id" value="deposits-methods">
        <div class="card-body">
            @foreach(\App\Classes\LegacyDeposits::getUserDepositMethods($user, true) as $key => $method)
                @if(!is_numeric($key))
                    <div class="form-group">
                        <div class="col-sm-10">
                            <div class="form-check checkbox">
                                <input class="form-check-input" type="checkbox" value="1"
                                       name="disable-{{ $key }}" {{ $settings->{"disable-$key"} == 1 ? '' : 'checked'}}>
                                <label class="form-check-label">
                                    All {{ ucfirst($key) }}
                                </label>
                            </div>
                        </div>
                    </div>
                    @foreach($method as $sub_method)
                        <div class="form-group">
                            <div class="col-sm-10">
                                <div class="form-check checkbox">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           name="disable-{{ $sub_method }}" {{ $settings->{"disable-$sub_method"} == 1 ? '' : 'checked'}}>
                                    <label class="form-check-input">
                                        {{ ucfirst($sub_method) }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="form-group">
                        <div class="col-sm-10">
                            <div class="form-check checkbox">
                                <input class="form-check-input" type="checkbox" value="1"
                                       name="disable-{{ $method }}" {{ $settings->{"disable-$method"} == 1 ? '' : 'checked'}}>
                                <label class="form-check-label">
                                    {{ strlen($method) == 2 ? strtoupper($method) : ucfirst($method) }}
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="card-footer">
            <button id="edit-deposits-methods" value="deposits-methods" type="submit" class="btn btn-info float-right">
                Update enabled deposits methods
            </button>
        </div>
    </form>
</div>

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            $('#edit-deposits-methods').click(function (e) {
                e.preventDefault();
                var dialogTitle = 'Edit payment solutions';
                var dialogMessage = 'Are you sure you want to edit the deposits methods user info?';
                var form = $("#deposits-methods");
                showConfirmInForm(dialogTitle, dialogMessage, form);
            });
        });
    </script>
@endsection
