<div class="simple-box pad-stuff-ten" id="gamebreak_24">
    <div class="account-headline"><?= t($headline) ?></div>
    <?php foreach($description as $text): ?>
        <p><?= t($text)?></p>
    <?php endforeach ?>

    <?php foreach($checkboxes_options as $option): ?>
        <div style="float: left;">
            <input type="checkbox"
                   class="hours-24-lock-games"
                   id="lockgamescat_<?=  $option['alias'] ?>"
                   value="<?= $option['checked'] ? '' : $option['alias'] ?>"
                   <?= $option['checked'] ? 'checked' : "" ?>
                   <?= $option['disabled'] ? 'disabled' : "" ?>
            >
            <label for="lockgamescat_<?= $option['alias'] ?>">
                <?= phive('Localizer')->getPotentialString($option['name']) ?>
            </label>
        </div>
    <?php endforeach ?>
    <div class="right">
        <br clear="all"/>
        <br clear="all"/>
        <button class="btn btn-l btn-default-l w-100" data-games="true" onclick="lockGames24Hours(1)">
            <?= t($buttons['save']) ?>
        </button>
    </div>
    <br clear="all"/>
</div>

<script>
    // select/unselect all the tags when clicking on "all_categories"
    $('#gamebreak_24').on('click', 'input[type="checkbox"]', function(event){
        if(event.target.value == 'all_categories') {
            $.each($('#gamebreak_24 input[type="checkbox"]'), function(index, el){
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