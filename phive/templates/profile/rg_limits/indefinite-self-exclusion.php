<div class="simple-box pad-stuff-ten margin-ten-top left">
    <div class="account-headline"><?php et($headline) ?></div>
    <?php foreach($description as $text): ?>
        <?= t($text) ?>
    <?php endforeach; ?>
    <button id="excludebtn_indefinite" class="btn btn-l btn-default-l w-150 right">
        <?php et($buttons['save']) ?>
    </button>
    <br clear="all" />
</div>