<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/SimpleExpandableBoxBase.php';

class MobilePaymentOptionsBox extends SimpleExpandableBoxBase {

    function init() {
    }

    function printHtml() {

        ?>

<div class="mobile-payment-options">

<?php et('mobile.payopt.top') ?>

<?php // TODO check if this can come from DB or some Config file /Paolo
$payment_options = [
    "Euteller",
    "Entropay",
    "Interac" => ["title" => "Interac / e-Transfer", "localized_string" => "interac"],
    "Trustly",
    "Zimpler" => ["title" => "Zimpler - Mobile payments made simple", "localized_string" => "zimpler"],
    "SIRU" => ["title" => "SIRU Mobile", "localized_string" => "sirumobile"],
    "Visa/Mastercard" => ["title" => "Visa / MasterCard", "localized_string" => "visamastercard"],
    "Skrill" => ["title" => "SKRILL (Moneybookers)", "localized_string" => "skrill"],
    "Neteller",
    "Citadel" => ["title" => "Instant Banking by Citadel", "localized_string" => "citadel"],
    "EcoPayz",
    "Flexepin",
    "Neosurf",
    "Paysafecard",
    "SMSVoucher" => ["title" => "SMSVoucher", "localized_string" => "smsvoucher"],
    "BankWire" => ["title" => "Bank Wire", "localized_string" => "bankwire"],
    "InstaDebit" => ["title" => "InstaDebit", "localized_string" => "instadebit"],
    "Klarna",
];

usort($payment_options, function($a, $b) {
    if (is_array($a)) {
        $a = $a['title'];
    }

    if (is_array($b)) {
        $b = $b['title'];
    }

    if ($a === $b) {
        return 0;
    }

    return $a > $b ? 1 : -1;
});

?>

<script>

    function filterPaymentOptions() {
        var filter_str = $("#filter-mobile-payment-options").val().trim();

        $('#mobile-payment-options-table > tbody > tr').each(function() {
            $tr = $(this);
            $tr.show();
            if (filter_str.length > 0) {
                var p = $tr.data('payopt');
                s = filter_str.split(" ");
                s.forEach(function(v) {
                    v = v.trim();
                    if (v.length > 0) {
                        if (!p.match(new RegExp(v, 'ig'))) {
                            $tr.hide();
                        }
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        <?php
        foreach ($payment_options as $payment_option):
            $var = strtolower($payment_option);
            if (is_array($payment_option)) {
                $var = $payment_option['localized_string'];
            }
            // TODO refactor this JS to use event delegation instead of putting multiple "click" functions /Paolo
        ?>

        $("#<?php echo $var ?>-toggle").click(function() {
            var $button_span = $(this).parents('span');
            var class_closed = 'mobile-payment-option-span-button-closed';
            var class_open   = 'mobile-payment-option-span-button-open';

            if ($button_span.hasClass(class_closed)) {
                $button_span.removeClass(class_closed);
                $button_span.addClass(class_open);
            } else if ($button_span.hasClass(class_open)) {
                $button_span.removeClass(class_open);
                $button_span.addClass(class_closed);
            }

            $("#<?php echo $var ?>-content").toggle(100);
        });
        <?php endforeach ?>


        $("#filter-mobile-payment-options").keyup(function() {
            filterPaymentOptions();
        });

    });
</script>

<br/>
<h2><?php et('mobile.payopt.available.deposit.withdraw'); ?></h2>
<br/>

<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
    <symbol xmlns="http://www.w3.org/2000/svg" id="sbx-icon-search" viewBox="0 0 40 41">
        <path d="M25.54 28.188c-2.686 2.115-6.075 3.376-9.758 3.376C7.066 31.564 0 24.498 0 15.782 0 7.066 7.066 0 15.782 0c8.716 0 15.782 7.066 15.782 15.782 0 4.22-1.656 8.052-4.353 10.884l1.752 1.75 1.06-1.06L40 37.332l-3.72 3.72-9.977-9.976 1.062-1.062-1.826-1.826zm-9.758.746c7.264 0 13.152-5.888 13.152-13.152 0-7.263-5.888-13.152-13.152-13.152C8.52 2.63 2.63 8.52 2.63 15.782c0 7.264 5.89 13.152 13.152 13.152z" fill-rule="evenodd" />
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="sbx-icon-clear" viewBox="0 0 20 20">
        <path d="M8.96 10L.52 1.562 0 1.042 1.04 0l.522.52L10 8.96 18.438.52l.52-.52L20 1.04l-.52.522L11.04 10l8.44 8.438.52.52L18.96 20l-.522-.52L10 11.04l-8.438 8.44-.52.52L0 18.96l.52-.522L8.96 10z" fill-rule="evenodd" />
    </symbol>
</svg>

<form novalidate="novalidate" onsubmit="return false;" class="searchbox sbx-custom">
    <div role="search" class="sbx-medium__wrapper">
        <input id="filter-mobile-payment-options" type="search" name="search" placeholder='<?php et("filter.payment.options"); ?>' autocomplete="off" required="required" class="sbx-custom__input">
        <button type="submit" title="Submit your search query." class="sbx-custom__submit">
            <svg role="img" aria-label="Search">
                <use xlink:href="#sbx-icon-search"></use>
            </svg>
        </button>
        <button id="reset-search-mobile-payment-options" type="reset" title="Clear the search query." class="sbx-custom__reset">
            <svg role="img" aria-label="Reset">
                <use xlink:href="#sbx-icon-clear"></use>
            </svg>
        </button>
    </div>
</form>

<table id="mobile-payment-options-table">
<tbody>

<!--
    <tr>
        <td width="15%">&nbsp;</td>
        <td width="70%">&nbsp;</td>
    </tr>
-->

    <?php
    foreach ($payment_options as $payment_option):
        $var = strtolower($payment_option);
        $title = ucfirst($payment_option);
        if (is_array($payment_option)) {
            $var   = $payment_option['localized_string'];
            $title = $payment_option['title'];
        }
    ?>

    <tr class="mobile-payment-option-tr-brief" data-payopt="<?php echo $title; ?>">
        <td class="mobile-payment-option-td-image">
            <div class="mobile-payment-option-div-image">
                <!-- <img src="<?php echo fupUri('payment_options/'. $var . '.png'); ?>" width="80px" height="53px" /> -->
                <img src="<?php echo fupUri('payment_options/'. $var . '.png'); ?>"/>
            </div>
        </td>
        <td class="mobile-payment-option-td-brief">
            <div class="mobile-payment-option-div-brief">
                <div class="mobile-payment-option-header">
                    <h4><?php echo $title; ?>
                        <?php if (t("mobile.payopt.provider.".$var.".content") != "(mobile.payopt.provider.".$var.".content)"): ?>
                        <span class="mobile-payment-option-span-button mobile-payment-option-span-button-closed">
                            <span id="<?php echo $var; ?>-toggle" class="mobile-payment-button">
                                <i class="icon-vs-chevron-left"></i>
                            </span>
                        </span>
                        <?php endif ?>
                    </h4>
                </div>
                <div>
                    <span class="mobile-payment-option-brief">
                        <?php et("mobile.payopt.provider.".$var.".brief"); ?>
                    </span>
                </div>
            </div>
        </td>
    </tr>

    <tr class="mobile-payment-option-tr-content" data-payopt="<?php echo $title; ?>">
        <td class="mobile-payment-option-td-content" colspan="2" style="text-align: justify;">
            <div id="<?php echo $var; ?>-content" class="mobile-payment-option-div-content hidden">
                <hr />
                <?php et("mobile.payopt.provider.".$var.".content"); ?>
            </div>
        </td>
    </tr>

    <?php endforeach ?>

</tbody>
</table>

<hr />

<?php et('mobile.payopt.bottom') ?>

</div>
        <?php
    }

    function printExtra() {
    }

}

