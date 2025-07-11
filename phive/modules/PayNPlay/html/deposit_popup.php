<?php

use Laraphive\Domain\Payment\Constants\PspActionType;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once '../../../phive.php';
require_once 'deposit_withdrawal_popup_variables.php';


$DesktopDepositBoxBase = phive('BoxHandler')->getRawBox('DesktopDepositBox');
$currency = lic('getForcedCurrency', []);

$predefinedAmounts = $DesktopDepositBoxBase->initializeCashierWithUserCurrency($currency);

$listOfAmounts = [];

foreach ($predefinedAmounts as $key => $value) {
    $listOfAmounts[] = [
        'value' => $value,
        'selected' => ($key === array_keys($predefinedAmounts)[1]),
        'currency' => $currency,
        'id' => 'amount_' . $value,
    ];
}

$selectedObject = array_values(array_filter($listOfAmounts, function ($var) {
    return $var['selected'] == true;
}));

$selectedAmount = ! empty($selectedObject) ? $selectedObject[0] : ['value' => 0, 'currency' => $currency];


$c = phive('Cashier');
$psp = 'trustly';
$minAmount = mc(phiveApp(PspConfigServiceInterface::class)->getLowerLimit($psp, PspActionType::IN));
$maxAmount = mc(phiveApp(PspConfigServiceInterface::class)->getUpperLimit($psp, PspActionType::IN));
?>

<div class="deposit_popup">
    <?= moduleHtml(
        'PayNPlay',
        'payment_provider_selector',
        false,
        null,
        [
            'action_type' => 'deposit',
            'userDepositPsps' => $userDepositPsps,
            'withdrawalEnabledPsps' => $withdrawalEnabledPsps,
            'selectedPsp' => $selectedPsp,
            'hasMultipleWithdrawOptions' => false,
        ]
    ) ?>


    <?= moduleHtml(
    'PayNPlay',
    'deposit_amount_input',
    false,
    null,
    ['selectedAmount' => $selectedAmount, 'minAmount' => $minAmount, 'maxAmount' => $maxAmount, 'showTitle' => true]
) ?>
    <div class="deposit_popup__amounts">
        <?php foreach ($listOfAmounts as $key => $value): ?>
            <div>
                <div class="deposit_popup__amounts_radio_btn">
                    <input type="radio" id="<?= $value['id'] ?>"
                           onclick="PayNPlay.changeAmount('<?= $value['id'] ?>', '<?= $value['value'] ?>')"
                           value="<?= $value['value'] ?>"
                           name="amount" <?= $value['selected'] ? 'checked' : null ?> />
                    <label for="<?= $value['id'] ?>">
                        <div class="deposit_popup__amounts_amount_text">
                            <span><?= $value['value'] ?></span>
                            <span class="margin-left"><?= $value['currency'] ?></span>
                        </div>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="deposit_popup__tnc">
        <div class="deposit_popup__tnc-layout">
            <div class="deposit_popup__tnc-info_icon"><img src="/diamondbet/images/<?= brandedCss() ?>info.svg"></div>
            <div class="deposit_popup__tnc-vertical-line"></div>
            <div class="deposit_popup__tnc-description">
                <?php if (phive()->isMobile()) : ?>
                    <span><?= et('paynplay.deposit.description.mobile') ?></span>
                <?php else: ?>
                    <span><?= et('paynplay.deposit.description') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="deposit_popup__action_btn">
        <?php if (! isLogged()) { ?>
            <button class="btn btn-l deposit_popup-btn deposit_popup-btn--deposit-btn"
                    onclick="PayNPlay.depositStart(true, null, 1)"><?= et('paynplay.deposit.btn') ?></button>
        <?php } else { ?>
            <button class="btn btn-l deposit_popup-btn deposit_popup-btn--deposit-btn"
                    onclick="PayNPlay.depositFromAccount()"><?= et('paynplay.deposit.btn') ?></button>
        <?php } ?>

        <?php
        if (! isLogged() || (isPNP() && p('account.pnp.login'))) {
            ?>
            <button class="btn btn-l deposit_popup-btn deposit_popup-btn--no-deposit-btn login-no-deposit"
                    onclick="PayNPlay.loginWithoutDeposit()"><?= et('paynplay.login.without.deposit') ?></button>
            <?php
        }
?>
    </div>
</div>

<?php
if ($currentUser instanceof DBUser && count($currentUser->getBonusesToForfeitBeforeDeposit())) {
    echo <<<JS
        <script type='text/javascript'>
            extBoxAjax(
                'get_html_popup', 'forfeit-bonus-to-deposit',
                {file:'forfeit_bonus_to_deposit',closebtn:'yes',boxid:'deposit-forfeit-box',boxtitle:'Message',module:'Micro'},
                {width:'450px',containerClass:'flex-in-wrapper-popup'}
            );
        </script>
        JS;
}
?>
