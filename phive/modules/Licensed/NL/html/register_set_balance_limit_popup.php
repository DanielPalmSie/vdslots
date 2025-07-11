<div id="register-set-account-balance-limit" class="limits-deposit-set account-balance-limit popup-limit">
    <div class="half">
        <img src="/diamondbet/images/time-limit-setup.png">
        <h3><?php et("registration.set.account.balance.limit.title") ?></h3>
        <p>
            <span><?php et("registration.set.account.balance.limit.description.part1") ?></span>
            <span><?php et("registration.set.account.balance.limit.description.part2") ?></span>
        </p>
    </div>
    <div class="half gray">
        <form action="javascript:" onsubmit="return licFuncs.rgLimitPopupHandler().saveRgLimit(event, 'balance')">
            <div>
                <label for="account-balance-limit" class="fat">
                    <span><?php et("registration.account.balance.limit.description") ?></span>
                    <span class="limits-deposit-set__unit right">(<?php echo cs() ?>)</span>
                </label>
                <input placeholder="0"
                       class="input-normal big-input full-width flat-input"
                       oninput="this.value = licFuncs.rgLimitPopupHandler().validateLimit('na', 'balance', this.value)"
                       name="balance"
                       maxlength="8"
                       id="popup-balance-limit-na"
                       value=""
                />
                <span class="error hidden"><?php et('post-registration.balance-limit.invalid') ?></span>
            </div>
            <div>
                <button type="submit"
                        class="btn btn-l positive-action-btn account_limit_popup_btn">
                    <span><?php et('rg.info.limits.set.confirm') ?></span>
                </button>
            </div>
        </form>
    </div>
</div>




