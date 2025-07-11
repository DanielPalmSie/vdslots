<div class="idscan_popup">
    <div class="idscan_popup__main-content">
        <div class="idscan_popup__logo">
            <div class="idscan_popup__logo-icon">
                <img src="/diamondbet/images/idscan/result-success.svg">
            </div>
        </div>
        <div class="idscan_popup__description">
            <div class="result__content-text result__content-text-bold"><?= et('idscan.identity.verification_success'); ?></div>
        </div>
    </div>
    <div class="idscan_popup__actions result__page-btn">
        <button class="btn btn-l btn-default-l lic-mbox-container-flex__button deposit_popup-btn"
                onclick="licFuncs.onIdScanContinue()"><?= et('idscan.upload.button.Continue'); ?></button>
    </div>
</div>

