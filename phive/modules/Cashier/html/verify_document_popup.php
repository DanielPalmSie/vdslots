<?php
    $documents_url = phive('UserHandler')->getUserAccountUrl('documents');
?>

<div class="verify_document__withdrawal">
    <div class="verify_document__body">
        <div class="verify_document__body-image">
            <img src="/diamondbet/images/<?= brandedCss() ?>warning.png" alt="warning image">
        </div>
    </div>

    <div class="verify_document__body-description">
        <div class="description">
            <?php et('verify.document.popup.description')?>
        </div>
    </div>

    <div class="verify_document__action">
        <button class="btn btn-l btn-default-l lic-mbox-container-flex__button verify_document-body-btn verify_document-body-btn-txt">
            <div onclick="goTo('<?php echo llink($documents_url) ?>')"><?php et('verify.document.button') ?></div>
        </button>
    </div>
</div>
