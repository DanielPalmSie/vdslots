<?php

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

require_once __DIR__ . '/../CashierNotify.php';

$notify_handler = new CashierNotify();

$input = file_get_contents('php://input');
$args = json_decode($input, true);

try {
    $res = $notify_handler->defaultInit();
} catch (AccessDeniedHttpException $e) {
    phive('Logger')
        ->getLogger('payments')
        ->warning("MTS_NOTIFY: Access denied", $args);

    http_response_code($e->getStatusCode());

    $notify_handler->stopFail($e->getMessage());
}

if ($res !== true) {
    $notify_handler->stop($res);
}

try {
    $res = $notify_handler->executeDefault();
} catch (UnprocessableEntityHttpException $e) {
    phive('Logger')
        ->getLogger('payments')
        ->error("Deposit does not exist", [
            "mts_id" => $args['data']['id'] ?? null,
            'args' => $args,
        ]);

    http_response_code($e->getStatusCode());

    $notify_handler->stopFail($e->getMessage());
}

$notify_handler->stop($res);
