<?php
require_once __DIR__ . '/../../../phive.php';

/*
   [
   {
   "event": "hard_bounce",
   "msg": {
   "ts": 1365109999,
   "subject": "This an example webhook message",
   "email": "example.webhook@mandrillapp.com",
   "sender": "example.sender@mandrillapp.com",
   "tags": [
   "webhook-example"
   ],
   "state": "bounced",
   "metadata": {
   "user_id": 111
   },
   "_id": "exampleaaaaaaaaaaaaaaaaaaaaaaaaa",
   "_version": "exampleaaaaaaaaaaaaaaa",
   "bounce_description": "bad_mailbox",
   "bgtools_code": 10,
   "diag": "smtp;550 5.1.1 The email account that you tried to reach does not exist. Please try double-checking the recipient&#39;s email address for typos or unnecessary spaces."
   },
   "_id": "exampleaaaaaaaaaaaaaaaaaaaaaaaaa",
   "ts": 1452607762
   },

   {
   "event": "unsub",
   "msg": {
   "ts": 1365109999,
   "subject": "This an example webhook message",
   "email": "example.webhook@mandrillapp.com",
   "sender": "example.sender@mandrillapp.com",
   "tags": [
   "webhook-example"
   ],
   "opens": [
   {
   "ts": 1365111111
   }
   ],
   "clicks": [
   {
   "ts": 1365111111,
   "url": "http:\\\/\\\/mandrill.com"
   }
   ],
   "state": "sent",
   "metadata": {
   "user_id": 111
   },
   "_id": "exampleaaaaaaaaaaaaaaaaaaaaaaaaa1",
   "_version": "exampleaaaaaaaaaaaaaaa"
   },
   "_id": "exampleaaaaaaaaaaaaaaaaaaaaaaaaa1",
   "ts": 1452607762
   },

   {
   "event": "reject",
   "msg": {
   "ts": 1365109999,
   "subject": "This an example webhook message",
   "email": "example.webhook@mandrillapp.com",
   "sender": "example.sender@mandrillapp.com",
   "tags": [
   "webhook-example"
   ],
   "opens": [
   
   ],
   "clicks": [
   
   ],
   "state": "rejected",
   "metadata": {
   "user_id": 111
   },
   "_id": "exampleaaaaaaaaaaaaaaaaaaaaaaaaa2",
   "_version": "exampleaaaaaaaaaaaaaaa"
   },
   "_id": "exampleaaaaaaaaaaaaaaaaaaaaaaaaa2",
   "ts": 1452607762
   }
   ]
 */

phive()->dumpTbl('mandrill-hook', $_REQUEST);
$uh = phive('UserHandler');
$events = json_decode($_REQUEST['mandrill_events'], true);
//if($_REQUEST['event'] == 'unsubscribe'){
  foreach($events as $e){
    $u = $uh->getUserByEmail($e['msg']['email']);
    phive()->dumpTbl('mandrill-event', $e);
    phive()->dumpTbl('mandrill-user', $u->data);
    if(!empty($u)){
      $u->unsubscribe();
      $uh->logAction($u, "Unsubscribed by Mandrill because: {$e['event']}", 'mandrill-'.$e['event']);
    }
  }
//}

