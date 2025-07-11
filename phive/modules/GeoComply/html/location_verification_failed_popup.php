<?php
$geoComply = phive('GeoComply');

$geoComply->incFailedGeolocations();
list($failedAttempt, $minutesLeft) = $geoComply->getFailedGeolocation();

?>
<div class="geo_comply height-100">
    <div class="geo_comply__install_image text-center">
        <img src="/diamondbet/images/geocomply/verification_failed_icon.svg">
    </div>
    <div class="geo_comply__content flex-1">
        <div class="geo_comply__content__title text-bold"><?php et('problem.info') ?></div>
    </div>
    <div class="geo_comply__app_info">
        <span class="troubleshooter text-underline"><?php et('check.troubleshooter') ?></span>
    </div>
    <div class="geo_comply__btn-section">
        <button class="geo_comply__download-btn geo_comply__btn-orange-color" onclick="GeocomplyModule.locateClick()" <?php if($minutesLeft){ ?> disabled<?php } ?>>
            <span class="text-bold"><?php et('retry') ?></span>
        </button>
    </div>
    <?php if($minutesLeft){ ?>

    <div class="geo_comply__verification_blocked">
        <?php echo t2('geocomply.inform.retry.limit', ['minutesLeft' => $minutesLeft]); ?>
    </div>

    <?php } ?>

</div>
