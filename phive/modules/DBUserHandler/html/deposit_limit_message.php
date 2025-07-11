<?php
$timespan = $_POST['timespan'] ?? '';
?>



<div>
    <div class="dialog__message--centered error-txt" >
        <? et('deposit_limit_warning_title') ?>
    </div>
    <hr/>
    <p class="dialog__message--centered">
        <? et('deposit_limit_warning_message') ?>
    </p>
    <p class="dialog__message--centered error-txt">
        <? et('deposit_limit_warning_reset') ?>
        <?php echo $timespan; ?>
    </p>
</div>
<div>
    <?php btnDefaultL('OK', '', "mboxClose('deposit_limit_message')", 100) ?>
</div>
<script>
    function closePopup(box_id, closeMobileGameOverlay) {
        closeMobileGameOverlay = closeMobileGameOverlay || false;
        if(parent.$('#vs-popup-overlay__iframe').length && closeMobileGameOverlay) {
            parent.$('.vs-popup-overlay__header-closing-button').click();
        }
        return  $.multibox('close', box_id);
    }
</script>
