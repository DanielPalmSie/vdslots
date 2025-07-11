<?php
require_once __DIR__ . '/../../../phive.php';
$mh = phive('MailHandler2');
$user = $mh->unsubscribe($_GET['e'], $_GET['t']);
$msg = $mh->getSetting('unsubscribe_msg');
if(empty($msg))
  $msg = 'You have been unsubscribed, you can subscribe again by editing your preferences.';
echo $msg;
