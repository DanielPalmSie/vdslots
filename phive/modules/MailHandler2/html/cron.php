<?php
require_once __DIR__ . '/../../../phive.php';

$mh = phive('MailHandler2');

// Send high priority mail if any (probably mails that failed)
$mh->sendMailQueue(0);

// Send queued mail
$mh->sendMailQueue(1);