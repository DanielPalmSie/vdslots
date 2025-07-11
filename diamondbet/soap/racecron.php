<?php
require_once __DIR__ . '/../../phive/phive.php';
$GLOBALS['is_cron'] = true;
if (isCli()) {
    if (phive('Race')->getSetting('clash_of_spins') === true) {
        if (phive('Race')->initNewRaceTemplates()) {
            phive('Race')->initNewRaces();
        }
    }
    phive('Race')->calcPrizes();
}
