<input name="<?= $input['name'] ?>" value="" type="text" id="<?= $input['name'] ?>" class="input-normal">
<br clear="all" />
<br clear="all" />
<strong>
    <?= t($labels['or']) ?>
</strong>
<br clear="all" />
<br clear="all" />
<input id="dk-indefinite" type="checkbox" name="<?= $checkbox_option['name'] ?>" value="" />
<?= t($checkbox_option['alias']) ?>

<div id="lock_error" class="error" style="display: none">
    <?= t('profile.lock-section.empty-selection-error') ?>
</div>
