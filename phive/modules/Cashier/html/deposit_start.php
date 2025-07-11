<?php
require_once __DIR__ . '/../../../phive.php';

if ($_POST['action'] != 'withdraw') {
    $handler = phive('Cashier/DepositStart');
    $args = [$_POST['action'], 'card', $_POST];
} else {
    $handler = phive('Cashier/WithdrawStart');
    $args = ['card', $_POST];
}


$result = $handler->init();
if ($result !== true) {
    phive('Logger')
        ->getLogger('payments')
        ->warning(strtoupper($_POST['action']) . ' INIT', [
            'result' => $result,
            'user_id' => isset($handler->u_obj) && isset($handler->u_obj->userId)
                ? $handler->u_obj->userId
                : null,
            'POST' => $_POST
        ]);

    $handler->failStop($result);
}

$result = call_user_func_array([$handler, 'execute'], $args);

if (is_string($result)) {
    phive('Logger')
        ->getLogger('payments')
        ->warning(strtoupper($_POST['action']) . ' EXECUTE', [
            'result' => $result,
            'user_id' => isset($handler->u_obj) && isset($handler->u_obj->userId)
                ? $handler->u_obj->userId
                : null,
            'POST' => $_POST
        ]);

    $handler->failStop($result);
}

$handler->stop($result);
