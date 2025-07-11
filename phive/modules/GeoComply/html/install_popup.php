<?php
?>
<div class="geo_comply">
    <div class="geo_comply__install_image text-center">
        <img src="/diamondbet/images/geocomply/map_icon.svg">
    </div>
    <div class="geo_comply__content">
        <div class="geo_comply__content__title"><?php et('before.continue') ?></div>
        <div class="geo_comply__content__desc"><?php et('follow.instruction') ?></div>

        <div class="geo_comply__content__desc-point geo_comply__install_points">
            <ol>
                <li>
                    <?php phive()->isMobile() ? et('download.install'): et('download.install.desktop') ?>
                </li>
                <li><?php et('open.installer') ?></li>
                <li><?php et('return.to.browser') ?></li>
            </ol>
        </div>
    </div>
    <div class="geo_comply__btn-section">
        <button class="geo_comply__download-btn geo_comply__btn-blue-color center-both-direction" id="geo_comply_download">
            <img src="/diamondbet/images/geocomply/download_icon.svg" />
            <span><?php et('download.app') ?></span>
        </button>
    </div>
    <div class="geo_comply__app_info">
        <?php phive()->isMobile() ? et('geoguard.app.info'): et('geoguard.app.info.desktop') ?>
    </div>
</div>

