<?php
$nationalities = $birthcountries = lic('getNationalities');

if (phive()->isMobile()): ?>
    <div class="lic-mbox-container limits-info mobile nationality-main-popup">
        <div class="center-stuff">
            <p>
                <?php et('select.nationalityandpob.description') ?>
            </p>

            <div class="nationality-select-box">
                <span class="styled-select">
                     <?php dbSelect('nationality-select', array_merge(['' => t('nationality.default.select.option')], $nationalities)) ?>
                </span>
                <br />
                <p class="error hidden nationality-error"><?php et('nationality.error.description') ?></p>
            </div>

            <div>
                <?php dbInput('place-of-birth-input', '', 'text', 'place-of-birth-input', '', true, false, false, t('register.birth_place')) ?>
                <br />
                <p class="error hidden place-of-birth-error"><?php et('place.of.birth.error.required') ?></p>
            </div>
        </div>

        <br/>
        <div class="center-stuff province-footer">
            <button class="btn btn-l btn-default-l w-100-pc" onclick="licFuncs.nationalityPopupHandler().sendSelected()"><?php et('confirm') ?></button>
        </div>
    </div>
<?php else: // Desktop ?>
    <div class="lic-mbox-container nationality-main-popup">
        <div class="center-stuff">
            <p>
                <?php et('select.nationalityandpob.description') ?>
            </p>

            <div class="nationality-select-box">
                <span class="styled-select">
                     <?php dbSelect('nationality-select', array_merge(['' => t('nationality.default.select.option')], $nationalities)) ?>
                </span>
                <br />
                <p class="error hidden nationality-error"><?php et('nationality.error.description') ?></p>
            </div>

            <div>
                <?php dbInput('place-of-birth-input', '', 'text', 'place-of-birth-input', '', true, false, false, t('register.birth_place')) ?>
                <br />
                <p class="error hidden place-of-birth-error"><?php et('place.of.birth.error.required') ?></p>
            </div>
        </div>

        <br/>
        <div class="center-stuff province-footer">
            <button class="btn btn-xl btn-default-xl w-100-pc" onclick="licFuncs.nationalityPopupHandler().sendSelected()"><?php et('confirm') ?></button>
        </div>
    </div>
<?php endif ?>
