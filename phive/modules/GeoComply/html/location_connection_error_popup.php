<?php
?>
<div class="geo_comply height-100">
    <div class="geo_comply__install_image text-center">
        <img src="/diamondbet/images/geocomply/connection_error_icon.svg">
    </div>
    <div class="geo_comply__content flex-1">
        <div class="geo_comply__content__title text-bold"><?php et('connection.error') ?></div>
        <div class="geo_comply__content__desc text-center"><?php et('connection.error.info') ?></div>
    </div>
    <div class="geo_comply__btn-section">
        <button class="geo_comply__download-btn geo_comply__btn-orange-color" onclick="GeocomplyModule.locateClick()">
            <span class="text-bold"><?php et('retry') ?></span>
        </button>
    </div>
    <div class="geo_comply__app_info">
        <span class="troubleshooter text-underline"><?php et('check.troubleshooter') ?></span>
    </div>

</div>
