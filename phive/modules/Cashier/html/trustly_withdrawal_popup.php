<?php

use Laraphive\Domain\Payment\Constants\PspActionType;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once "../../BoxHandler/boxes/diamondbet/CashierWithdrawBoxBase.php";

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

$withdrawCashier = new CashierWithdrawBoxBase();
$withdrawCashier->init();

$box_id = $_POST['box_id'] ?? 'trustly_withdrawal_popup';

$psp = 'trustly';
$c = phive('Cashier');
$deduct = $c->getDisplayDeduct($psp, $u_obj);

$min_amount = phiveApp(PspConfigServiceInterface::class)->getLowerLimit($psp, PspActionType::OUT, $u_obj);
$max_amount = phiveApp(PspConfigServiceInterface::class)->getUpperLimit($psp, PspActionType::OUT, $u_obj);
?>

<style>

    .trustly__withdrawal .trustly__body {
        width: 100%;
        box-sizing: border-box;
    }

    .trustly__withdrawal .trustly__body-image {
        width: 100%;
        height: 260px;
    }

    .trustly__withdrawal .trustly__body-description{
        width: 100%;
        height: auto;
        font-size: 18px;
        font-weight: 400;
        line-height: 21px;
        text-align: center;
        padding: 10px 0;
    }

    .trustly__withdrawal .trustly__body-image img {
        width: auto;
        height: 100%;
    }

    .trustly__withdrawal .trustly__body-description {
        font-size: 18px;
        font-style: normal;
        font-weight: 400;
        line-height: 21px;
    }

    .trustly__withdrawal .trustly__separator {
        border-bottom: 1px solid #C5BEBC;
    }

    .trustly__withdrawal .trustly__body__amount-limits {
        display: flex;
        font-size: 14px;
        font-weight: 400;
        line-height: 16px;
        text-align: center;
        justify-content: space-between;
        padding: 10px;
    }

    .trustly__withdrawal .vertical-bar {
        color: #C5BEBC;
    }

    .trustly__withdrawal input[type=text],
    .trustly__withdrawal select {
        font-size: 16px;
    }

    .trustly__withdrawal .amount_value{
        font-weight: 700;
        line-height: 16px;
        font-size: 14px;
        padding-left: 10px;
    }

    .trustly__withdrawal .trustly__action {
        text-align: left;
    }

    .trustly__withdrawal form {
        font-size: 16px;
    }

    .trustly__withdrawal form .label {
        text-align: left;
        padding: 10px 0;
        font-weight: 400;
        line-height: 18px;
    }

    .trustly__withdrawal form input,
    .trustly__withdrawal form select {
        box-sizing: border-box;
        width: 100%;
        height: 47px;
        border-radius: 5px;
        padding-left: 10px;
        padding-right: 10px;
        border: 1px solid #d1d1d1;
    }

    .trustly__withdrawal form select#account_select {
        margin-top: unset;
        color: black;
    }

    .trustly__withdrawal .trustly-body-btn {
        margin-top: 10px;
        position: relative;
        border-radius: 6px;
        height: 43px;
    }

    .trustly__withdrawal .blue_success{
        background: #0099FF;
        color: white;
    }

    .trustly__withdrawal .bank_logo {
        position: absolute;
        right: 10px;
        color: #0099FF;
        background-color: white;
        padding: 5px 10px;
        top: 5px;
        border-radius: 5px;
    }

    .trustly__withdrawal .trustly-deposit-body-btn-txt {
        font-weight: 700;
        font-size: 16px;
        line-height: 21px;
    }
</style>

<div class="trustly__withdrawal">
    <div class="trustly__body">
        <div class="trustly__body-image">
            <img class="net-deposit-body-img" src="/diamondbet/images/trustly-withdrawal-popup.png">
        </div>
        <div class="trustly__body-description">
            <div class="description">
                <?php et('withdraw.with.trustly.description')?>
            </div>
        </div>
        <div class="trustly__separator"></div>
        <div class="trustly__body__amount-limits">
            <div class="initial_fee">
               <span>Fee</span><span class="amount_value"><?php echo $deduct ?></span>
            </div>
            <span class="vertical-bar">|</span>
            <div class="min-limit">
                <span>Min</span><span class="amount_value"><?php efEuro(mc($min_amount, $u_obj)) ?></span>
            </div>
            <span class="vertical-bar">|</span>
            <div class="max-limit">
                <span>Max</span><span class="amount_value"><?php efEuro(mc($max_amount, $u_obj)) ?></span>
            </div>
        </div>
        <div class="trustly__amounts_form">
            <form id="popup-withdrawForm-trustly">
                <div class="label">
                    <label for="account_select"><?php et('choose.bank_account') ?>:</label>
                </div>
                <?php
                    dbSelectWith(
                        'account_select',
                        $withdrawCashier->getDisplaySourcesBySubKey('banks', 'trustly'),
                        'encrypted_account_ext_id',
                        ['display_name', 'closed_loop_formatted'],
                        '',
                        [],
                        'required'
                    );
                ?>

                <div class="label">
                    <label for="amount">Amount:</label>
                </div>
                <input type="text" id="amount" name="amount" value="" class="number required">
            </form>
        </div>
    </div>

    <div class="trustly__action">
        <button class="btn btn-l lic-mbox-container-flex__button trustly-body-btn trustly-deposit-body-btn-txt blue_success" onclick="withdrawWithTrustly()">
            <?php et('trustly.withdraw.button') ?>
            <div class="bank_logo">
                <span>BANK</span>
            </div>

        </button>
        <button class="btn btn-l btn-default-l lic-mbox-container-flex__button trustly-body-btn trustly-deposit-body-btn-txt">
            <div onclick="withdrawWithOther()"><?php et('other.withdraw.button') ?></div>
        </button>
    </div>
</div>

<script>
    let wf = $('#popup-withdrawForm-trustly');
    wf.validate();

    function withdrawWithTrustly() {
        cashier.withdraw.postTransaction(wf);
    }

    function withdrawWithOther() {
        mboxClose('<?php echo $box_id ?>');
    }

    function closePopup(box_id, redirectOnMobile, closeMobileGameOverlay) {
        mboxClose('<?php echo $box_id ?>');
    }
</script>
