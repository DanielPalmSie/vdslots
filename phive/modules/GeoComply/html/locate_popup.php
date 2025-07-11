<?php
?>
<div class="geo_comply height-100">
    <div class="geo_comply__install_image text-center">
        <img src="/diamondbet/images/geocomply/locate_icon.svg">
    </div>
    <div class="geo_comply__content flex-1">
        <div class="geo_comply__content__title text-bold"><?php et('verify.your.location') ?></div>
        <div class="geo_comply__content__desc"><?php et('verify.location.info') ?></div>
    </div>
    <div class="geo_comply__btn-section">
        <button class="geo_comply__download-btn geo_comply__btn-orange-color center-both-direction" onclick="GeocomplyModule.locateClick()">
            <img src="/diamondbet/images/geocomply/location_icon.svg" />
            <span class="text-bold"><?php et('verify.your.location') ?></span>
        </button>
    </div>
</div>
