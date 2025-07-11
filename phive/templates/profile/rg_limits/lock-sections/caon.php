<div class="rg-radios-duration-exclude rg-button-ontario">
    <?php foreach($bullet_options as $option): ?>
        <div class="left">
            <input class="left"
                   type="radio"
                   name="lock_duration"
                   value="<?= $option['value'] ?>"
                   <?= $option['checked'] ? 'checked' : '' ?>
            >
            <div class="left" style="margin-top: 2px;">
                <?= t($option['alias']) ?>
            </div>
        </div>
    <?php endforeach ?>

    <input id="ca-other"
           class="left"
           type="radio"
           name="<?= $other_option['name'] ?>"
           value="<?= $other_option['value'] ?>"
    >
    <div class="left" style="margin-top: 2px;">
        <?= t($other_option['alias']) ?>
    </div>
</div>

<div id="ca-lock-txt-holder" style="display: none;">
    <input name="<?= $input['name'] ?>" value="" type="text" id="<?= $input['name'] ?>" class="input-normal">
</div>

<div id="lock_error" class="error" style="display: none">
    <?= t('profile.lock-section.empty-selection-error') ?>
</div>
