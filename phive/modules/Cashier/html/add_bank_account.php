<?php

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../AddAccountService.php';

$addAccountService = new AddAccountService();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'printAdditionalFields':
        $addAccountService->printTrustlyBankAdditionalFields();
        break;

    case 'addBankAccount':
        echo $addAccountService->addBankAccount($_POST['data']);
        break;

    case 'getBankAccountDetails':
        echo json_encode($addAccountService->getAccountNumberInfo($_POST['bankCountry']));
        break;
}

exit;
