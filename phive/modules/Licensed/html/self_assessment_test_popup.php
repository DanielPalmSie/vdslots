<?php
$mbox = new MboxCommon();
$user = $mbox->getUserOrDie();
$user->deleteSetting("force_self_assessment_test");
$country_data = phive('Localizer')->getCountryData();
?>
<?php if(empty(phive()->isMobile())): ?>
    <style>
        /* Leave this css here to overwrite the default width only for this case */
        .multibox-outer {
            width: 90vh !important;
        }
        .lic-mbox-container {
            padding: 0;
        }
        iframe {
            height: 70vh;
        }
    </style>
<? else: ?>
    <style>
        #mbox-msg {
            width: 100% !important;
        }
        iframe {
            height: 100vh;
        }
    </style>
<? endif; ?>

<iframe style="width: 100%; border: 0;" src="<?php echo lic('getGamTestUrl', [$user, $country_data['langtag']], $user) ?>"></iframe>
