<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;


require_once '../../../phive.php';

$currentUser = cu();
$pspConfigService = phiveApp(PspConfigServiceInterface::class);

// Determine previous user deposit PSPs, first one being the latest.
$depositOptions = ['trustly', 'swish'];
if ($currentUser) {
    $userDepositPsps = phive('Cashier')->getDepositPspsByUserId(cu()->getId());
}
if (empty($userDepositPsps)) {
    $userDepositPsps = $depositOptions;
}

// Construct array of config-enabled PSPs.
$withdrawalEnabledPsps = [];
if ($pspConfigService->getPspSetting('trustly', 'withdraw')['active']) {
    $withdrawalEnabledPsps[] = 'trustly';
}
if ($pspConfigService->getPspSetting('swish', 'withdraw')['active']) {
    $withdrawalEnabledPsps[] = 'swish';
}

// Determine which PSP should be selected.
$selectedPsp = null;
foreach ($withdrawalEnabledPsps as $withdrawalEnabledPsp) {
    if ($withdrawalEnabledPsp === $userDepositPsps[0]) {
        $selectedPsp = $withdrawalEnabledPsp;
    }
}
if (!$selectedPsp) {
    $selectedPsp = $withdrawalEnabledPsps[0];
}
?>
