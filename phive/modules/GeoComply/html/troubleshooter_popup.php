<?php
$content = '';
$retry_button_action = $_REQUEST['context'] == 'global-check' ? "GeocomplyGlobalChecks.retryButtonAction()" : "GeocomplyModule.locateClick()";

if ($_REQUEST['troubleshooter']) {
    phive('GeoComply')->log("troubleshooter-displayed", $_REQUEST, "notice");
    if (is_string($_REQUEST['troubleshooter'])) {
        $content = $_REQUEST['troubleshooter'];
    } else {
        $content = $_REQUEST['troubleshooter']['message'];
    }
}

$geoComply = phive('GeoComply');

if($_REQUEST['context'] == 'global-check'){
    $geoComply->incFailedGeolocations();
}

list($failedAttempt, $minutesLeft) = $geoComply->getFailedGeolocation();

?>
<div class="geo_comply__content flex-1 overflow-scroll text-left">

    <?php
    if (!empty($content)) {
        if (is_array($content)) {
            foreach ($content as $line) {
                ?>
                <div class="geo_comply__content__desc">
                    <?= nl2br($line) ?>
                </div>
                <?php
            }
        } elseif (is_string($content)) {
            echo nl2br($content);
        }
    }
    ?>
</div>

<div class="geo_comply__btn-section">
    <button class="geo_comply__download-btn geo_comply__btn-orange-color" onclick="<?= $retry_button_action ?>" <?php if($minutesLeft){ ?> disabled<?php } ?>>
        <span class="text-bold"><?php et('retry') ?></span>
    </button>
</div>

<?php if($minutesLeft){ ?>

    <div class="geo_comply__verification_blocked">
        <?php echo t2('geocomply.inform.retry.troubleshooter', ['minutesLeft' => $minutesLeft]); ?>
    </div>

<?php } ?>




