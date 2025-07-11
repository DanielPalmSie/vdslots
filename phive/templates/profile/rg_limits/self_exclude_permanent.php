<div class="simple-box pad-stuff-ten margin-ten-top left">
    <div class="account-headline"><?= t($headline) ?></div>
    <?php foreach($description as $text) : ?>
        <p><?= t($text) ?></p>
    <?php endforeach ?>
    <button id="excludebtn_permanent" class="btn btn-l btn-default-l w-150 right">
        <?= t($buttons['save']) ?>
    </button>
    <br clear="all" />
</div>