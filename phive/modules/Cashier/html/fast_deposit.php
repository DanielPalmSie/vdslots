<?php

$u_obj = cuPl();

$more_options_click = $_REQUEST['context'] == 'mobile-play-page' ? "showCashierDeposits()" : "parent.mboxDeposit('".llink('/cashier/deposit/')."')";

$psp = $_REQUEST['psp'];
$network = phive('Cashier')->getPspRoute($u_obj, $psp);
$transaction_error = $_REQUEST['transaction_error'];
// We need to use the desktop version as it is the one with the predefined amounts.
$cbox = phive('BoxHandler')->getRawBox('DesktopDepositBox', true);
$cbox->init();

$channel = phive()->isMobile() ? 'mobile' : 'desktop';

/*
if($channel == 'mobile'){
    $fields_box = phive('BoxHandler')->getRawBox('MobileDepositBox', true);
    $fields_box->init();
} else {
    $fields_box = $cbox;
}
*/

// We set the correct channel for the extra fileds override.
$extra_fields = $cbox->getExtraFieldsOverride($network);

$predef_amounts = $cbox->getPredefAmounts($psp);

$prior_amount = $cbox->getOverriddenFastDepositPriorAmount()
    ?? $cbox->getDefaultFastDepositPriorAmount($predef_amounts);

// We get the prior deposit from the PSP in question.
$prior_deposit = phive('Cashier')->getDepositsForRepeat($u_obj, [$psp], [], 1)[0];
if(!empty($prior_deposit)){
    // In case we have a deposit we get the closest level.
    $prior_amount = phive()->getLvl($prior_deposit['amount'] / 100, $predef_amounts, $prior_amount);
}

$repeats = null;
$repeat_type = null;

phive('Casino')->setJsVars($channel == 'mobile' ? 'mobile' : 'normal');

echo '<script async type="text/javascript" src="https://pay.google.com/gp/p/js/pay.js"></script>';
loadJs("/phive/js/googlepay.js");
loadJs("/phive/js/cashier.js");
loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css");
loadCss("/diamondbet/css/" . brandedCss() ."fast_deposit.css");

if ($channel === 'mobile') {
    loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");
}

loadJs("/phive/js/handlebars.js");
loadJs("/phive/js/jquery.validate.min.js");
loadJs('/phive/js/jquery.json.js');
$cbox->setCashierJs();
$cbox->generateHandleBarsTemplates();
?>
<div class="fast-deposit">
    <div class="lic-mbox-header relative">
        <div class="lic-mbox-close-box" onclick="theCashier.getTargetFrame().mboxClose('fast-deposit-box')"><span class="icon icon-vs-close"></span></div>
        <img src="<?php echo fupUri($psp."-fast-desktop.png") ?>">
    </div>

    <?php if(!empty($_REQUEST['extraContent'])): ?>
        <br/>
        <br/>
        <?php et($_REQUEST['extraContent']) ?>
    <?php endif ?>

    <div class="lic-mbox-container">

        <div class="fast-deposit__form-area">

                <div class="fast-deposit_predefs">
                    <?php foreach($predef_amounts as $display_amount => $debit_amount): ?>
                        <?php
                        $predefinedAmountElementID = "cashier-predefined-amount-" . $display_amount;
                        ?>

                        <div id="<?php echo $predefinedAmountElementID ?>" class="cashier-predefined-amount cashier-predefined-amount-number fast-deposit-box" onclick="theCashier.setAmount('<?php echo $display_amount ?>')">
                            <script>
                                var predefinedAmountElement = document.getElementById("<?php echo $predefinedAmountElementID ?>");
                                var formattedAmount  = formatCashierAmount(<?php echo $display_amount; ?>);
                                predefinedAmountElement.innerHTML = formattedAmount;
                            </script>
                        </div>
                    <?php endforeach ?>
                </div>


                <div class="amount-other-container">
                    <div id="predef-amount-other" class="cashier-predefined-amount cashier-predefined-amount-other fast-deposit-box">
                        <?php echo strtoupper(t('other.sum')) ?>:
                    </div>
                    <input id="deposit-amount" <?php if($cbox->hasPredefAmounts($psp)) echo 'disabled="disabled"' ?> name="amount" value="" class="other-amount-field fast-deposit-box" type="<?php echo $cbox->ifMobElse('tel', 'number') ?>">
                    <span class="other-amount-currency-symbol"><?php echo ciso()?></span>
                </div>

            <?php if($cbox->hasPredefAmounts($psp)): ?>
                <table class="zebra-tbl">
                    <tr class="odd">
                        <td class="fee-label">
                            <?php et("expenses") ?>
                        </td>
                        <td>
                            <span id="fee-percentage" class="cashier-expense cashier-fee-number"></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fee-label">
                            <?php et("credit.amount") ?>
                        </td>
                        <td>
                            <span id="credit-amount" class="cashier-expense"></span>
                        </td>
                    </tr>
                    <tr class="odd">
                        <td class="fee-label">
                            <?php et("debit.amount") ?>
                        </td>
                        <td>
                            <span id="debit-amount" class="cashier-expense"></span>
                        </td>
                    </tr>
                </table>
            <?php endif ?>

            <div id="deposit-cashier-box">
                <form id="deposit-form" class="fast-deposit__form-area-deposit">
                    <?php
                        if(!empty($extra_fields)){
                            // This outputs special fields in addition to returning repeat type and repeats data.
                            // It is confusing and should be refactored. /Henrik
                            list($repeat_type, $repeats) = $extra_fields();
                        }
                    ?>
                    <?php $cbox->generateExtraFields($psp, false) ?>
                </form>
            </div>
        </div>

        <div id="fast-deposit-action-area" class="fast-deposit__action-area" style="<?php echo !empty($repeats) && $repeat_type == 'ccard' ? 'display: none;' : '' ?>">
            <button class="fast-deposit__action-area-btn positive-action-btn" onclick="theCashier.postDeposit('deposit')">
                <?php et('deposit') ?>
            </button>

            <div class="fast-deposit__action-area-link" onclick="<?php echo $more_options_click ?>">
                <?php et('click.here.more.poptions') ?>
            </div>
        </div>

    </div>

    <?php
    // print_r([$repeats]);
    ?>

    <?php $cbox->setPspJson() ?>

    <script>

        var transaction_error = '<?php echo $transaction_error ?>';
        if (transaction_error != false) {
            console.log("transaction_error", transaction_error);
            depositLimitMessage(transaction_error, true);
        }

     function showCashierDeposits(){
         mboxClose();
         $('.vs-button__deposits').click();
     }

     theCashier.postDeposit = function(){
         return this.postTransaction('deposit', false, $('#deposit-amount').val());
     }

     var psp = '<?php echo $psp ?>';

     theCashier.defaultPredefAmounts = <?php echo json_encode($predef_amounts) ?>;

     var predefs = theCashier.defaultPredefAmounts;

     cashier.currentPsp     = psp;
     cashier.currentPredefs = predefs;
     cashier.isFastDeposit  = true;

     theCashier.setAmount('<?php echo $prior_amount ?>');

    </script>


</div>
<?php
$cbox->afterPrintHTML();
