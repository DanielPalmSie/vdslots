<?php
?>
<div class="geo_comply">
    <div class="geo_comply__install_image text-center">
        <div class="multi_image_container">
            <img src="/diamondbet/images/geocomply/checking_locate_icon.svg">
            <img class="inner_image" src="/diamondbet/images/geocomply/download_icon.svg">
        </div>
        <div class="loader__container">
            <div class="dot-circle"></div>
            <div class="dot-circle"></div>
            <div class="dot-circle"></div>
            <div class="dot-circle"></div>
            <div class="dot-circle"></div>
        </div>
    </div>
    <div class="geo_comply__content">
        <?php if (function_exists('phive')) { ?>
            <div class="geo_comply__content__title text-bold"><?php et('check.in.progress') ?></div>
            <div class="geo_comply__content__desc text-center"><?php et('check.in.progress.info') ?></div>
        <?php } ?>
    </div>
</div>