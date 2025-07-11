<div class="simple-box pad-stuff-ten margin-ten-top left">
    <div class="account-headline"><?= t($headline) ?></div>

    <?php foreach($description as $text): ?>
        <?= t2($text, ['cooloff_period' => $cooloff_period]) ?>
    <?php endforeach; ?>

    <button id="limbtn_lock" class="btn btn-l btn-default-l w-150 right">
        <?= t($buttons['submenu']) ?>
    </button>
    <br clear="all" />

    <form id="limform_lock" class="account-sub-box hidden" action="javascript:">
        <div class="account-sub-middle">
            <div><?= t($submenu) ?></div>
            <br clear="all" />

            <?= $form ?>

            <br clear="all" />
            <br clear="all" />
            <button id="cancelbtn_lock" class="btn btn-l btn-cancel-l w-auto min-w-100" type="button">
                <?= t($buttons['remove']) ?>
            </button>
            <span class="account-spacer">&nbsp;</span>
            <button id="lockbtn" class="btn btn-l btn-default-l w-auto min-w-100" type="submit">
                <?= t($buttons['save']) ?>
            </button>
        </div>
    </form>
</div>
<br clear="all" />
