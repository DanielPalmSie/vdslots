<?php

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../SelectAccountService.php';

$selectAccountService = new SelectAccountService($_POST['supplier']);
echo $selectAccountService->selectAccount();
exit;
