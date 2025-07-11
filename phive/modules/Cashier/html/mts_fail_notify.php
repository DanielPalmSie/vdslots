<?php

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

require_once __DIR__ . '/../CashierNotify.php';

$notify_handler = new CashierNotify();

$args = $notify_handler->getBase64Body($_POST);

try {
    $notify_handler->init();
} catch (AccessDeniedHttpException $e) {
    phive('Logger')
        ->getLogger('payments')
        ->warning("MTS_FAIL_NOTIFY: Access denied", $args);

    http_response_code($e->getStatusCode());

    $notify_handler->stopFail($e->getMessage());
    exit();
}

$res = $notify_handler->executeFail($args);

$notify_handler->stop($res);
