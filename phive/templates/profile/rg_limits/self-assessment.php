<div class="simple-box pad-stuff-ten margin-ten-top left">
    <div class="account-headline">
        <?php et($headline) ?>
    </div>
    <?php foreach($description as $text): ?>
        <?= t($text) ?>
    <?php endforeach; ?>
    <br clear="all" />
    <br clear="all" />
    <button
        class="btn btn-l btn-default-l take-test-btn"
        onclick="licFuncs.doGamTest('<?php echo $link ?>')"
    >
        <?php et($buttons['navigate']) ?>
    </button>
</div>
<br clear="all" />
<br clear="all" />
