<?php
    $box_id = $_POST['box_id'] ?? 'general_info_popup';
    $description = $_POST['boxDescription'];
    $textReplacements = $_POST['textReplacements'];
    $reloadPage = filter_var($_POST['reloadPage'], FILTER_VALIDATE_BOOLEAN);
?>

<div class="general_info">
    <div class="general_info__body-description">
        <div class="description text-container">
            <?php et2($description, $textReplacements); ?>
        </div>
    </div>

    <div class="general_info__action">
        <button class="mbox-ok-btn btn btn-l btn-default-l" onclick="handleClose()">OK</button>
    </div>
</div>

<script>
    function handleClose() {
        mboxClose('<?php echo $box_id; ?>');

        const shouldReload = <?php echo json_encode($reloadPage); ?>;
        if (shouldReload) {
            window.location.href = window.location.pathname;
        }
    }

    function closePopup(redirectOnMobile, closeMobileGameOverlay) {
        handleClose();
    }
</script>
