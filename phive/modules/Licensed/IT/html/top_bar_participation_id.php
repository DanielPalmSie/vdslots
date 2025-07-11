<?php /* The timer is required because game session is created shortly after page load */
    if (empty($_REQUEST['eid']) && empty($_REQUEST['show_demo'])):
?>
<div class="top-bar-menuitem" id="top-bar-participation-id">
    <?= t('session.balance.participation.id') ?><br><div id="top-bar-participation-id-value"></div>
</div>

<div class="top-bar-menuitem" id="top-bar-participation-id">
    <?= t('session.balance.aams.session.id') ?><br><div id="top-bar-ext-session-id-value"></div>
</div>
<?php  endif;  ?>