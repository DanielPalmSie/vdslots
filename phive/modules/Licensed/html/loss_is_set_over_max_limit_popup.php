<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

$body_string = $_POST['bodyString'] ?? 'loss-limit.set.over.maximum.html';
$button_string = $_POST['buttonString'] ?? 'OK';
$box_id = $_POST['box_id'] ?? 'mbox-msg';
$max_loss_limit = lic('getHighestAllowedLossLimit', [$u_obj], $u_obj) ?? PHP_INT_MAX;
$max_loss_limit_coins = $max_loss_limit / 100;
?>

<div class="lic-mbox-wrapper">
    <?php
    $top_part_data = (new TopPartFactory())->create($box_id, 'msg.title');
    $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container">
        <?php echo t2($body_string, ['loss_limit' => $max_loss_limit_coins . cs()]); ?>
        <?php btnDefaultXl(t($button_string), '', "closePopup()", null, 'margin-ten-top') ?>
    </div>
</div>
<script>
    function closePopup() {
        mboxClose('<?php echo $box_id ?>');
        jsReloadBase();
    }
</script>
