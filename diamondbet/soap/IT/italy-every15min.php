<?php
ini_set('max_execution_time', '1800');

require_once '/var/www/videoslots.it/phive/phive.php';

if(isCli()){
    $GLOBALS['is_cron'] = true;
    lics('gameSessionsAlignmentCommunicationCron');
}
