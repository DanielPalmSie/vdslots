<?php

require_once __DIR__ . '/../../phive/phive.php';

if (isCli()) {
    $GLOBALS['is_cron'] = true;
    try {
        phive('IpBlock')->downloadGeoIpDatabase();
    } catch (Exception $e) {
        phive('Logger')->error('Cron - Downloading GeoIP2-Country database failed.', [$e]);
    }

    try {
        phive('IpBlock')->downloadGeoIpDatabase('city');
    } catch (Exception $e) {
        phive('Logger')->error('Cron - Downloading GeoIP2-City database failed.', [$e]);
    }
}
