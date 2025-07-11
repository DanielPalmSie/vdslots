<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

$box_id = $_POST['box_id'] ?? 'net-deposit-info-box';

$limits = rgLimits()->getByTypeUser($u_obj, 'net_deposit');
$reached_type = [];
$message = 'An RG interaction was conducted with the customer, as they reached their monthly recommended spending limit based on the affordability information we have on file. We have restricted their deposits until the end of their 30-day reset period and informed them of the situation. Additionally, we encouraged the customer to take a break from gambling and review their limits using our responsible gambling tools.';
foreach ($limits as $rgl) {
    if (rgLimits()->isReached($rgl, 0, true, false) && $reached_type != 'week') {
        $reached_type = $rgl['time_span'];
    }
}

if (!$u_obj->hasCommentCurrentMonth('rg-action', substr($message, 0, 21))) {
    $u_obj->addComment($message, 0, 'rg-action');
}

if (empty($reached_type)) {
    error_log("Fatal Error on net_deposit_info_box.php, reached type not found");
    die(jsRedirect(phive('Casino')->getBasePath()));
}

?>
<style>
    .net-deposit-body {
        text-align: justify;
    }
    .net-deposit-body-btn {
        margin-top: 10px;
    }
</style>

<?php if(phive()->isMobile()): ?>
    <style>
        .lic-mbox-container {
            text-align: center;
        }
        /* Default landscape rules */
        @media screen and (orientation: landscape) {
            .net-deposit-body-btn {
                width: 49%;
            }
            .net-deposit-body-img{
                display: none;
            }
            .net-deposit-body-h3 {
                display: none;
            }
            .net-deposit-body-btn-txt {
                font-size: 14px;
            }
        }
    </style>
<?php endif; ?>

<div class="lic-mbox-wrapper <?= $_POST['extra_css'] ?: '' ?>" id="lic-mbox-wrapper-<?= $box_id ?>">
    <?php
        $top_part_data = (new TopPartFactory())->create(
            $_POST['box_id'] ?? 'mbox-msg',
            $_POST['boxtitle'] ?? 'msg.title',
            $_POST['closebtn'] == 'no'
        );
        $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container">
        <img class="net-deposit-body-img" src="/diamondbet/images/<?= brandedCss() ?>time-limit-setup.png">
        <h3 class="net-deposit-body-h3"><?php et("net.deposit.limit.info.{$reached_type}.header") ?></h3>
        <div class='net-deposit-body'><?php et("net.deposit.limit.info.{$reached_type}.body.html") ?></div>
        <button class="btn btn-l positive-action-btn good-green lic-mbox-container-flex__button net-deposit-body-btn">
            <div class="net-deposit-body-btn-txt" onclick="requestIncrease()"><?php et('net.deposit.limit.info.request.increase.button') ?></div>
        </button>
        <button class="btn btn-l btn-default-l lic-mbox-container-flex__button net-deposit-body-btn">
            <div class="net-deposit-body-btn-txt" onclick="closePopup('<?= $box_id ?>', true, false)"><?php et('net.deposit.limit.info.request.accept.button') ?></div>
        </button>
    </div>
</div>

<script>

    function closeNetDepositInfoPopUp() {
        if (isMobile()) {
            var rgNetDepositInfoBox = document.getElementById('lic-mbox-wrapper-<?= $box_id ?>');
            rgNetDepositInfoBox.style.display = "none";
        } else {
            closePopup('<?= $box_id ?>', true, false);
        }
    }

    function requestIncrease() {
        var closeSelf = '<?= $_POST['noRedirect'] || !phive()->isMobile() ? $box_id : null ?>';
        var rg_login_info_callback = '<?= phive('Licensed')->getRedirectBackToLinkAfterRgPopup() ?>';
        mgAjax({action: 'request-net-deposit-limit-increase'}, function(ret){
            if (ret === 'nok') {
                jsReloadBase();
            } else {
                closeNetDepositInfoPopUp();
                mboxMsg('<?php et('net.deposit.limit.info.request.increase.success.message') ?>', true, function () {
                    if (parent.$('#vs-popup-overlay__iframe').length) {
                        parent.$('.vs-popup-overlay__header-closing-button').click();
                    }

                    if (!empty(closeSelf)) {
                        //$.multibox('close', 'mbox-msg');
                        //parent.$.multibox('close', closeSelf);
                        jsReloadBase();
                    } else if (!empty(rg_login_info_callback)) {
                        window.location.href = rg_login_info_callback;
                    } else {
                        jsReloadBase();
                    }
                }, 400);
            }
        });
    }
</script>
