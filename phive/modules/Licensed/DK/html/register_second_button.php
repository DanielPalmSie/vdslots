<?php
// Got here from registration step 2
if (!empty($_SESSION['rstep2'])) {
    return;
}

$data = require_once __DIR__ . '/_register_second_button_data.php';

?>
<div id="second-register-button">
    <br clear="all"/>
    <div class="register-button <?= $data['mit_id_disabled'] === true ? 'lic-mbox-btn-mit-id--disabled' : '' ?>">
        <div class="register-big-btn-txt register-button-second_denmark"
            <?= $data['mit_id_disabled'] === true
                ? ''
                : 'onclick="submitStep1(' . $data['post_data'] . ')"'
            ?>
        >
            <span><?php et($data['title_alias']) ?></span>
            <img class="register-button-second_denmark-img" src="<?php echo lic('imgUri', [$data['image'] . '.png']) ?>"/>
        </div>
        <?php if($data['mit_id_disabled'] === true): ?>
            <div class="lic-mbox-label-info-mit-id--unavailable" style="line-height: 16px;"><?php et($data['unavailable_title_alias']) ?></div>
        <?php endif ?>
    </div>
</div>