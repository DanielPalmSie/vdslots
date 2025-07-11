<?php
require_once __DIR__ . '/../../../api.php';

// http://docs.zignsec.com/api/webhooks/

$json = file_get_contents('php://input');

/** @var ZignSec $zsec */
$zsec = phive('DBUserHandler/BankId');

if ($zsec->isDebug()) {
    phive()->dumpTbl('zignsec-webhook', $json);
}

$zsec->verifyCallbackHash($json);
// We return 2xx here as we dno't want them to retry in case we've got internal logical issues.
$res = $zsec->handleBankIdCallback($json);

if ($zsec->isDebug()) {
    phive()->dumpTbl('zignsec-webhook-handle', $res);
}

echo json_encode($res);



