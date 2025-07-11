<?php
$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

$box_type = $_POST['boxType'] ?? 'tickbox';
$body_string = $_POST['bodyString'] ?? 'understand.accpolicy.html';
$button_string = $_POST['buttonString'] ?? 'i.understand.the.info.above';
$action = $_POST['action'] ?? 'viewed-account-policy';
$box_id = $_POST['box_id'] ?? 'mbox-msg';
$deposit_iframe_target = !!($_POST['depositIframeTarget'] ?? false);
$checkboxErrorMsg = $_POST['checkboxErrorMsg'] ?? false;
?>


<?php et($body_string) ?>
<?php if ($box_type == 'tickbox'): ?>

    <div class="account-message-box__checkbox-wrapper">
        <label for="tick-ok">
            <?php dbCheck('tick-ok') ?>
            <?php et('i.understand') ?>
        </label>
    </div>
    <?php if ($checkboxErrorMsg !== 'false'): ?>
        <div id="checkboxCheck" class="error-message"></div>
    <?php endif; ?>
    <?php btnDefaultXl(t($button_string), '', '', null, 'generic-info-popup__ok-btn margin-ten-top') ?>

<?php else: ?>
    <?php btnDefaultXl(t($button_string), '', '', null, 'generic-info-popup__close-btn margin-ten-top') ?>

<?php endif; ?>

<script>
    // IIFE is added here to make sure variables/functions declared inside don't interfere with other scripts
    // (for example, we have 'var target' global declaration in some GTM scripts)
    (function() {
        const depositIframeTarget = <?= $deposit_iframe_target ? 'true' : 'false' ?>;
        const depositIframe = $('#mbox-iframe-cashier-box')[0];

        let target = window;
        if (depositIframeTarget && depositIframe) {
            target = depositIframe.contentWindow;
        }

        function closePopup(redirect_on_mobile = false) {
            // we go to a new page on mobile because all the popups should be in a new page
            return isMobile() && redirect_on_mobile
                ? target.location.href = "<?php echo llink('/') ?>"
                : mboxClose('<?php echo $box_id ?>');
        }

        function okTickBox(action) {

            var checkboxCheck = $('#checkboxCheck');

            checkboxCheck.text('');

            if ($('input[name="tick-ok"]:checked').length === 0) {
                !!checkboxCheck && checkboxCheck.text('<?php et('generic.form.validation.checkboxRequired') ?>');
                return;
            }

            mgAjax({action: action}, function(ret){
                if (ret === 'nok') {
                    jsReloadBase();
                    return;
                }
                mboxClose('<?php echo $box_id ?>');
                target.execNextPopup();
            });
        }

        $('.generic-info-popup__ok-btn').on('click', function () {
            const action = '<?= $action ?>';
            okTickBox(action);
        });

        $('.generic-info-popup__close-btn').on('click', function () {
            closePopup();
        });
    })();
</script>
