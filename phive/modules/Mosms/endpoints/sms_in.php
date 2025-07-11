<?php 
require_once __DIR__ . '/../../../phive.php';

if ($_REQUEST['Password'] !== phive('Mosms')->getSetting('zignsec_password') ||
    $_SERVER['REMOTE_ADDR'] !== phive('Mosms')->getSetting('zignsec_ip'))
    die('access denied');

/*
   https://URL.COM/SMS_IN/?FromNumber=%q&Time=%t&ToNumber=%Q&Message=%a&Keyword=%s&Value=%s   
   FromNumber=Sender, ex. +46222222222  (Our customer's number)
   ToNumber=Reciever, ex +463333333333 (Longnumber that the message was sent to)
   Date/Time= the time the message was sent, formatted as "YYYY-MM-DD HH:MM", e.g., "1999-09-21 14:18"
   Message=all words of the SMS message, including the first one, with spaces squeezed to one
   Keyword= the keyword in the SMS request (i.e., the first word in the SMS message) For example STOP.
   Value= next word from the SMS message, starting with the second one ”810418000” (could be a customer reference or an answer of a question)
*/

$clean 	= phive("Mosms")->cleanUpNumber($_REQUEST['FromNumber']);
$u      = phive('UserHandler')->getUserByAttr('mobile', $clean);
if(empty($u))
    die('no user');

$action = strtolower($_REQUEST['Message']);

switch($action){
    case 'stop':
    case 'stopp':
        $u->setSetting('sms', 0);
        break;
    default:
        die('no action');
        break;
}

echo 'ok';

