<?php if (phive()->isMobile()): ?>
    <div class="lic-mbox-container limits-info mobile province-main-popup">
        <img src="/diamondbet/images/<?= brandedCss() ?>province-icon.png">
        <div class="center-stuff">
            <p>
                <?php et('select.province.description') ?>
            </p>

            <div class="province-select-box">
                <span class="styled-select">
                    <?php $provinces = lic('getProvinces') ?>
                    <?php dbSelect('province-select', array_merge(['' => t('province.default.select.option')], $provinces)) ?>
                </span>
                <br />
                <p class="error hidden province-error"><?php et('province.error.description') ?></p>
            </div>
        </div>

        <br/>
        <div class="center-stuff province-footer">
            <button class="btn btn-l btn-default-l w-200" onclick="licFuncs.provincePopupHandler().sendSelectedProvince()"><?php et('ok') ?></button>
        </div>
    </div>
<?php else: // Desktop ?>
    <div class="lic-mbox-container limits-info province-main-popup">
        <img src="/diamondbet/images/<?= brandedCss() ?>province-icon.png">
        <div class="center-stuff">
            <p>
                <?php et('select.province.description') ?>
            </p>

            <div class="province-select-box">
                <span class="styled-select">
                     <?php $provinces = lic('getProvinces') ?>
                     <?php dbSelect('province-select', array_merge(['' => t('province.default.select.option')], $provinces)) ?>
                </span>
                <br />
                <p class="error hidden province-error"><?php et('province.error.description') ?></p>
            </div>
        </div>

        <br/>
        <div class="center-stuff province-footer">
            <button class="btn btn-l btn-default-l w-200" onclick="licFuncs.provincePopupHandler().sendSelectedProvince()"><?php et('ok') ?></button>
        </div>
    </div>
<?php endif ?>