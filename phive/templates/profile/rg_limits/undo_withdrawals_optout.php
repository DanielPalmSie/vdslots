<div class="simple-box pad-stuff-ten" id="undo_widthdrawals">
    <div class="account-headline"><?= t($headline) ?></div>
    <?php foreach($description as $text): ?>
        <p><?= t($text)?></p>
    <?php endforeach ?>
    <div style="text-align: center">
        <?php foreach($bullet_options as $option): ?>
            <input type="radio"
                   name="undo_withdrawals"
                   id="<?= $option['name'] ?>"
                   value="<?= $option['value'] ?>"
                   <?= $option['checked'] ? "checked" : "" ?>
            >
            <label for="<?= $option['name'] ?>"><?= t($option['alias']) ?></label>
        <?php endforeach ?>
    </div>
    <div class="right">
        <br clear="all"/>
        <br clear="all"/>
        <button class="btn btn-l btn-default-l w-100" onclick="undoWithdrawalsOptInOut()">
            <?= t($buttons['save']) ?>
        </button>
    </div>
    <br clear="all"/>
</div>