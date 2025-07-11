<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$user = cu();
$mbox = new MboxCommon();

$limit = RgLimits()->getSingleLimit($user, 'balance');
$current_limit = $limit['cur_lim'];
$balance = $user->getBalance();
$exceeded_amount = $balance - $current_limit;

$action = $_POST['action'] ?? ''; // Can be deposit or game play
$amount = $_POST['amount'] ?? 0;

$content_title = $action === 'deposit' ? 'balance.limit.popup.balance_set_title' : 'balance.limit.popup.reached_title';
$content_details = $action === 'deposit' ? 'balance.limit.popup.balance_set_detail' : 'balance.limit.popup.reached_detail';

$box_id = "balance_limit_popup";
$close_popup_action = phive()->isMobile() ? "location.reload()" : "closePopup('{$box_id}', false, false)";
$ok_btn_action = ($action === 'game_play') ? "goTo('/')" : $close_popup_action;
$change_limit_link = (phive()->isMobile() ? "/mobile/" : "") . "/account/{$user->getId()}/responsible-gambling";
?>
<div class="balance-limit-popup">
    <?php
        $top_part_data = (new TopPartFactory())->create($box_id, 'balance.limit.popup.main_title', true);
        $mbox->topPart($top_part_data);
    ?>
    <img src="/diamondbet/images/deposit-limit-setup.png">
    <div class="balance-limit-popup__content">
        <h2><?php echo t($content_title) ?></h2>
        <p><?php echo t2($content_details, ['currency' => cs(), 'amount' => $amount]) ?></p>

        <div class="balance-limit-popup__details">
            <p>
                <strong><?php et('balance.limit.popup.maximum_allowed_balance') ?></strong>
                <strong><?php echo cs() . rnfCents($current_limit) ?></strong>
            </p>
            <p>
                <strong><?php et('balance.limit.popup.current_balance') ?></strong>
                <strong class="red"><?php echo cs() . rnfCents($balance) ?></strong>
            </p>
            <?php if ($exceeded_amount > 0) : ?>
                <p>
                    <strong><?php et('balance.limit.popup.exceeded_amount') ?></strong>
                    <strong class="red">
                        <?php echo cs() . rnfCents($exceeded_amount) ?>
                    </strong>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <div class="balance-limit-popup__form">
        <?php if ($action !== 'deposit'): ?>
            <div class="balance-limit-popup__button-container">
                <?php btnActionXL(t('balance.limit.popup.withdraw_now'), '', withdrawalGo(), '', 'btn-action-bg-flat btn-action-round') ?>
            </div>
        <?php endif; ?>
        <div class="balance-limit-popup__button-container">
            <?php btnActionXL(t('ok'), '', $ok_btn_action, '', 'btn-action-bg-flat btn-action-round') ?>
        </div>
        <?php if ($action === 'deposit'): ?>
            <div class="balance-limit-popup__button-container">
                <?php btnActionXL(t('balance.limit.popup.change_limit'), '', "goTo('$change_limit_link')", '', 'btn-action-bg-flat btn-action-round') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
