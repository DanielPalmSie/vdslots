<div class="simple-box pad-stuff-ten" id="gamebreak_indefinite">
    <div class="account-headline"><?= t($headline) ?></div>
    <?php foreach($description as $text): ?>
        <p><?= t($text) ?></p>
    <?php endforeach ?>

    <?php foreach($checkboxes_options as $option): ?>
        <?php if(empty($option['period'])): ?>
            <div style="float: left;">
                <input type="checkbox"
                       class="indefinite-lock-games"
                       id="lockgamescat_<?= $option['alias'] ?>"
                       value="<?= $option['alias'] ?>"
                       <?= $option['checked'] ? 'checked' : '' ?>
                >
                <label for="lockgamescat_<?= $option['alias'] ?>">
                    <?= phive('Localizer')->getPotentialString($option['name']) ?>
                </label>
            </div>
        <?php endif ?>
    <?php endforeach ?>
    <br clear="all"/>
    <br clear="all"/>
    <?php foreach($checkboxes_options as $option): ?>
        <?php if(!empty($option['period']) && $option['period'] != '0000-00-00 00:00:00'): ?>
            <span class="vip-color"><i><b><?= t('game-category-block-indefinite.unblock') ?>:</b></i></span>&nbsp;
            <span class="rg-limits-actions__extra-text">
                <i>
                    <?= phive('Localizer')->getPotentialString($option['name']) ?>
                    <?= phive()->lcDate($option['period'], '%x %R') ?>
                </i>
            </span>
            <br clear="all"/>
            <br clear="all"/>
        <?php endif ?>
    <?php endforeach ?>
    <div class="right">
        <button class="btn btn-l btn-default-l w-100" data-games="true" onclick="lockUnlockGamesIndefinite()">
            <?= $buttons['save'] ?>
        </button>
    </div>
    <br clear="all"/>
</div>
<script>
    $('#gamebreak_indefinite').on('click', 'input[type="checkbox"]', function(event){
        if(event.target.value === 'all_categories') {
            $.each($('#gamebreak_indefinite input[type="checkbox"]'), function(index, el){
                if(event.target.checked) {
                    el.checked = true;
                } else {
                    if(!el.disabled) {
                        el.checked = false;
                    }
                }
            });
        } else {
            // when unselecting a single category we uncheck "all_categories"
            if(!event.target.checked) {
                $('#lockgamescat_all_categories')[0].checked = false;
            }
        }
    });
</script>