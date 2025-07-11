<li class="games-footer__right">
    <a class="games-footer-link" href="<?= licSetting('bottom_regulator_url') ?>"  target="_blank" rel="noopener noreferrer" >
        <img src="<?php echo lic('imgUri', ['Logo_1_GameMode.png']) ?>"  class="games-footer__image">
    </a>
    <a class="games-footer-link" href="<?= licSetting('bottom_adm_url') ?>" target="_blank" rel="noopener noreferrer" >
        <img src="<?php echo lic('imgUri', ['ADM_Logo_GameMode.png']) ?>"  class="games-footer__image">
    </a>
</li>
<li class="games-footer__right">
    <a class="games-footer-link games-footer-18plus" href="<?= lic('get18PlusLink') ?>" target="_blank" rel="noopener noreferrer" >
        18<span class="plus">+</span>
    </a>
</li>
<li class="games-footer__right games-footer-license">
    <?= et('videoslots.license') ?>
</li>
