<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">Casino / Other settings</h3>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form id="other-settings" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-other-settings-update', ['user' => $user->id]) }}"
          method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="form_id" id="form_id" value="other-settings">
        <div class="card-body">
            @if(p('user.casino.settings.sub_aff_no_neg_carry'))
                <div class="form-group">
                    <label for="sub_aff_no_neg_carry">sub_aff_no_neg_carry</label>
                    <input type="text" name="sub_aff_no_neg_carry" class="form-control" placeholder=""
                           value="{{ $settings->sub_aff_no_neg_carry }}">
                </div>
            @endif
            @if(p('user.casino.settings.casino_loyalty_percent'))
                <div class="form-group">
                    <label for="casino_loyalty_percent">Casino Weekend Booster %</label>
                    <input type="text" name="casino_loyalty_percent" class="form-control" placeholder=""
                           value="{{ $settings->casino_loyalty_percent }}">
                </div>
            @endif
            @if(p('user.casino.settings.bonus_block'))
                <div class="form-group">
                    <div class="form-check checkbox">
                        <input class="form-check-input" type="checkbox" value="1"
                               name="bonus_block" {{ $settings->bonus_block == 1 ? 'checked' : ''}}>
                        <label class="form-check-label">Block user
                            from bonus use</label>
                    </div>
                </div>
            @endif
            @if(p('user.casino.settings.max_thirty_withdrawal'))
                <div class="form-group">
                    <label for="max_thirty_withdrawal">Max withdraval in 30 days</label>
                    <input type="text" name="max_thirty_withdrawal" class="form-control" placeholder=""
                           value="{{ $settings->max_thirty_withdrawal }}">
                </div>
            @endif
            @if(p('user.casino.settings.permanent_dep_lim'))
                <div class="form-group">
                    <label for="permanent_dep_lim">Permanent Deposit Limit (CENT €10 = 1000)</label>
                    <input type="text" name="permanent_dep_lim" class="form-control" placeholder=""
                           value="{{ $settings->permanent_dep_lim }}">
                </div>
            @endif
            @if(p('user.casino.settings.permanent_dep_period'))
                <div class="form-group">
                    <label for="permanent_dep_period">Permanent Deposit Period</label>
                    <input type="text" name="permanent_dep_period" class="form-control" placeholder=""
                           value="{{ $settings->permanent_dep_period }}">
                </div>
            @endif
            @if(p('user.casino.settings.free_deposits'))
                <div class="form-group">
                    <div class="form-check checkbox">
                        <input class="form-check-input" type="checkbox" value="1"
                               name="free_deposits" {{ $settings->free_deposits == 1 ? 'checked' : ''}}>
                        <label class="form-check-label">Free
                            deposits</label>
                    </div>
                </div>
            @endif
            @if(p('user.casino.settings.free_withdrawals'))
                <div class="form-group">
                    <div class="form-check checkbox">
                        <input class="form-check-input"type="checkbox" value="1"
                               name="free_withdrawals" {{ $settings->free_withdrawals == 1 ? 'checked' : ''}}>
                        <label class="form-check-label">Free
                            withdrawals</label>
                    </div>
                </div>
            @endif
            @if(p('user.casino.settings.withdraw_period'))
                <div class="form-group">
                    <label for="withdraw_period">Withdraw Period</label>
                    <input type="text" name="withdraw_period" class="form-control" placeholder=""
                           value="{{ $settings->withdraw_period }}">
                </div>
            @endif
            @if(p('user.casino.settings.withdraw_period_times'))
                <div class="form-group">
                    <label for="withdraw_period_times">Withdraw Period Times</label>
                    <input type="text" name="withdraw_period_times" class="form-control" placeholder=""
                           value="{{ $settings->withdraw_period_times }}">
                </div>
            @endif
            @if(p('user.casino.settings.lock-date'))
                <div class="form-group">
                    <label for="lock-date">Lock Date</label>
                    <input type="text" name="lock-date" class="form-control" placeholder=""
                           value="{{ $settings->{'lock-date'} }}">
                </div>
            @endif
            @if(p('user.casino.settings.affiliate_admin_fee'))
                <div class="form-group">
                    <label for="affiliate_admin_fee">Affiliate Admin Fee (10% default)</label>
                    <input type="text" name="affiliate_admin_fee" class="form-control" placeholder=""
                           value="{{ $settings->affiliate_admin_fee }}">
                </div>
            @endif
            @if(p('user.casino.settings.dep-limit-playblock'))
                <div class="form-group">
                    <label for="dep-limit-playblock">Daily deposit limit before play block</label>
                    <input type="text" name="dep-limit-playblock" class="form-control" placeholder=""
                           value="{{ $settings->{'dep-limit-playblock'} }}">
                </div>
            @endif
        </div>
        <!-- /.box-body -->
        <div class="card-footer">
            <button id="edit-other" name="form_id" value="other_settings" type="submit" class="btn btn-info float-right">
                Update Casino / Other Settings
            </button>
        </div>
        <!-- /.box-footer -->
    </form>
</div>

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            $('#edit-other').click(function (e) {
                e.preventDefault();
                var dialogTitle = 'Edit Casino / Other settings';
                var dialogMessage = 'Are you sure you want to edit the user casino settings?';
                var form = $("#other-settings");
                showConfirmInForm(dialogTitle, dialogMessage, form);
            });
        });
    </script>
@endsection
