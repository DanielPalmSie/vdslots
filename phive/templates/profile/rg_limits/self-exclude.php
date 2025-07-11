<div class="simple-box pad-stuff-ten margin-ten-top left">
    <div class="account-headline"><?= t($headline) ?></div>

    <?php foreach($description as $text): ?>
        <?= t($text) ?>
    <?php endforeach; ?>

    <button id="limbtn_exclude" class="btn btn-l btn-default-l w-150 right">
        <?= t($buttons['submenu']) ?>
    </button>
    <br clear="all" />
    <div id="limform_exclude" class="account-sub-box hidden">
        <div class="account-sub-middle">
            <div><?= t($submenu) ?></div>
            <br clear="all" />
            <div id="rg-duration-exclude">
                <div class="rg-radios-duration-exclude ">
                    <?php foreach ($bullet_options as $option): ?>
                        <div class="left">
                            <input class="left"
                                   type="radio"
                                   name="excl_duration"
                                   value="<?= $option['value'] ?>"
                                   <?= $option['checked'] ? "checked" : '' ?>
                            >
                            <div class="left" style="margin-top: 2px;">
                                <?= t($option['alias']) ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <button id="cancelbtn_exclude" class="btn btn-l btn-cancel-l w-100">
                <?= t($buttons['remove']) ?>
            </button>
            <span class="account-spacer">&nbsp;</span>
            <button id="excludebtn" class="btn btn-l btn-default-l w-100">
                <?= t($buttons['save']) ?>
            </button>
        </div>
    </div>
</div>