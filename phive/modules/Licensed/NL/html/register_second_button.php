<?php
// Got here from registration step 2
if (!empty($_SESSION['rstep2'])) {
    return;
}

$data = require_once __DIR__ . '/_register_second_button_data.php';
?>
<div id="second-register-button">
    <br clear="all"/>
    <div id="verify-redirect" class="register-button" onclick="submitStep1(<?= $data['post_data'] ?>)">
        <div class="<?= phive()->isMobile() ? 'register-big-btn-txt' : '' ?>">
            <?php et($data['title_alias']) ?>
            <img src="<?php echo lic('imgUri', [ $data['image'] . '.png']) ?>"/>
        </div>
    </div>
</div>
