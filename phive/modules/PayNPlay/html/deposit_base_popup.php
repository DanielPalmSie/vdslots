<?php
use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$box_id = $_POST['box_id'] ?? 'deposit_bank_details';

$mbox       = new MboxCommon();
$header    = $_POST['boxtitle'] ?? 'msg.title';
$allow_close_redirection = isset($_POST['allow_close_redirection']) ? ($_POST['allow_close_redirection'] == 'true') : true;

$top_part_factory = new TopPartFactory();

generateFingerprint(true);
?>
<div class="lic-mbox-wrapper version2">

    <?php
    $top_part_data = $top_part_factory->create($box_id, $header, false, $allow_close_redirection);
    $mbox->topPart($top_part_data);
    ?>

    <div class="lic-mbox-container minimal-padding country-<?=phive('Licensed')->getLicCountry()?>">

        <div id="deposit_bank_details_section"
             popup-title="<? et('paynplay.deposit') ?>"
             login-title="<? et('login') ?>"
             style="height: 100%;display: none">
            <?php moduleHtml('PayNPlay', 'deposit_bank_details', false, null) ?>
        </div>

        <div id="deposit_popup_section" popup-title="<? et('paynplay.deposit') ?>">
            <?php moduleHtml('PayNPlay', 'deposit_popup', false, null) ?>
        </div>

        <div id="deposit_success_section" popup-title="<? et('paynplay.deposit') ?>" style="display: none">
           <?php moduleHtml('PayNPlay', 'deposit_success', false, null) ?>
        </div>

        <div id="deposit-notification__first_success-section" popup-title="<? et('paynplay.deposit') ?>" style="display: none">
            <?php moduleHtml('Licensed', 'welcome_offer_activation_popup', false, null) ?>
        </div>

        <div id="deposit_failure_section" popup-title="<? et('paynplay.deposit') ?>" style="display: none">
            <?php moduleHtml('PayNPlay', 'deposit_failure', false, null) ?>
        </div>

    </div>
</div>
