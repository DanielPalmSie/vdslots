<?php
$nationalities = $birthcountries = lic('getNationalities');
$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
?>
<div class="lic-mbox-container nationality-main-popup <?= phive()->isMobile() ? 'limits-info mobile': '' ?> ">
    <div class="center-stuff">
        <p>
            <?php et('select.nationality.description') ?>
        </p>

        <div class="nationality-select-box">
            <span class="styled-select">
                 <?php dbSelect('nationality-select', array_merge(['' => t('nationality.default.select.option')], $nationalities)) ?>
            </span>
            <br />
            <p class="error hidden nationality-error"><?php et('nationality.error.description') ?></p>
        </div>
    </div>

    <br/>
    <div class="center-stuff province-footer">
        <button id="update_nationality" class="btn btn-l btn-default-l w-100-pc"><?php et('confirm') ?></button>
    </div>
</div>

<script>
    $('#update_nationality').click(function (e) {
        e.preventDefault();
        if (document.cookie.indexOf('redirect_after_login=') !== -1) {
            licFuncs.nationalityPopupHandler().sendCountrySelected(redirectToPreviousPage);
        } else {
            licFuncs.nationalityPopupHandler().sendCountrySelected();
        }
    });

    function redirectToPreviousPage() {
        window.location.href = '<?= lic('getRedirectBackToLinkAfterRgPopup', [], $u_obj) ?>';
    }
</script>
