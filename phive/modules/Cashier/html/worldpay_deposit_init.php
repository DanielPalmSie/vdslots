<?php

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

if (!empty($_GET['lang'])) {
    phive('Localizer')->setLanguage($_GET['lang'], true);
}

$user = cu();
setCur($user);

$mts = new Mts(Supplier::Worldpay);
$result = $mts->request('user/transfer/deposit/init', $mts->getBaseParams($user, 0, 'init'));

phive('Logger')->getLogger('worldpay')->debug("deposit_init_result", [
    'url' => $_SERVER['REQUEST_URI'] ?? null,
    'file' => __METHOD__ . '::' . __LINE__,
    'result' => $result,
]);

foreach ($result['errors'] ?? [] as $field => $errStr) {
    $result[$field] = t($errStr);
}

die(json_encode($result));
