<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $GLOBALS['is_cron'] = true;
    phive('Currencer')->updateCurrenciesInEur();

    phive()->pexec('Cashier/Arf', 'invoke', ['everydayCron']);

    $older_than = phive("Logger")->getSetting('trans_log_delete_older_than', '-1 week');
    phive("Logger")->deleteOlderThan('trans_log', 'created_at', $older_than);

    error_log("NO9". date('Y-m-d H:i:s'));

    phive("Logger")->deleteOlderThan('mailer_log', 'time_sent', '-3 day', 'mail_id');
    error_log("NO10". date('Y-m-d H:i:s'));
}
