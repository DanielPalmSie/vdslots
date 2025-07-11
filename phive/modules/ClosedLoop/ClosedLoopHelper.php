<?php

namespace ClosedLoop;

use CasinoCashier;
use DBUser;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

class ClosedLoopHelper
{
    public const STATUS_OPEN = 1;
    public const STATUS_CLOSED = 0;
    public const STATUS_PENDING_DISABLED = -1;

    private CasinoCashier $casinoCashier;

    public function __construct(CasinoCashier $casinoCashier)
    {
        $this->casinoCashier = $casinoCashier;
    }

    public function getAccountType(string $psp, string $identifier): ?string
    {
        $creditCardPsps = $this->casinoCashier->getSetting('ccard_psps');
        if (array_key_exists($psp, $creditCardPsps) && $this->casinoCashier->isCardHash($identifier)) {
            return 'card';
        }

        if (in_array($psp, $this->casinoCashier->getBankSuppliers())) {
            return 'bank';
        }

        return null;
    }

    public function determineLoopStatus(array $loopData): int
    {
        // There is still an outstanding balance to withdraw – the loop remains open.
        if ($loopData['total_deposit_amount'] > $loopData['total_withdraw_amount']) {
            return self::STATUS_OPEN;
        }

        // If there is a pending withdrawal with a rerouted amount exclusively for Trustly in depositOnlyClosedLoop scenario.
        if (
            $loopData['remaining_amount'] < 0 &&
            $loopData['pending_withdraw_amount'] > 0 &&
            $loopData['rerouted_deposit_amount'] > 0 &&
            $loopData['deposit_amount'] == 0 &&
            in_array($loopData['source_psp'], $this->casinoCashier->getBankSuppliers())
        ) {
            return self::STATUS_PENDING_DISABLED;
        }

        /*
         * All deposited amount has been withdrawn – the loop is closed.
         * Or if there are no pending withdrawals for an account with no deposits (e.g., select accounts).
        */
        if ($loopData['total_deposit_amount'] <= $loopData['approved_withdraw_amount']) {
            return self::STATUS_CLOSED;
        }

        /*
         * Pending and approved amounts equal the deposit amount,
         * so this option should be disabled until pending amounts are resolved.
        */
        return self::STATUS_PENDING_DISABLED;
    }

    public function validateClosedLoopWithdrawal(
        DBUser  $user,
        int     $withdrawAmountInCents,
        ?string $source,
        array   $closedLoopData
    ): array
    {
        if ($this->casinoCashier->getAntiFraudScheme($user) !== 'closed_loop' || empty($closedLoopData)) {
            return ['success' => true];
        }

        $sourceLoopData = $closedLoopData[$source] ?? null;

        if (count($closedLoopData) === 1 && $sourceLoopData) {
            return ['success' => true];
        }

        if ($sourceLoopData) {
            if ($sourceLoopData['status'] === self::STATUS_PENDING_DISABLED) {
                return ['success' => false, 'errors' => t('err.disabled.by.closed.loop'), 'translate' => false];
            }

            $sourceLoopData['remaining_amount'] = $this->calculateOverrideRemainingClAmount($sourceLoopData, $user);

            if (
                $sourceLoopData['status'] === self::STATUS_OPEN
                && $withdrawAmountInCents <= $sourceLoopData['remaining_amount']
            ) {
                return ['success' => true];
            }
        }

        $loopsLimitInfo = [];
        foreach ($closedLoopData as $identifier => $loopData) {
            $loopData['remaining_amount'] = $this->calculateOverrideRemainingClAmount($loopData, $user);

            if ($loopData['status'] === self::STATUS_OPEN) {
                $loopsLimitInfo[] = ucfirst($identifier) . ' ' . ciso() . ' ' . rnfCents($loopData['remaining_amount']);
            }
        }

        return [
            'success' => false,
            'error_msg_alias' => 'closed.loop.limit.overage.error',
            'error_msg_params' => [implode(', ', $loopsLimitInfo)],
            'errors' => t2('closed.loop.limit.overage.error', [implode(', ', $loopsLimitInfo)], $user),
            'translate' => false
        ];
    }

    public function skipClosedLoop(DBUser $user, string $psp, string $identifier): bool
    {
        // TODO: To be handled in BAN-12402 and BAN-12400
        if ($psp === 'swish') {
            return isPNP();
        }

        $accountType = $this->getAccountType($psp, $identifier);
        $methodType = ($accountType === 'card') ? 'ccard' : ($accountType === 'bank' ? $psp : $identifier);

        $allowedWithdrawalPsps = $this->casinoCashier->getAllAllowedPsps(
            $user,
            'withdraw',
            phive()->isMobile() ? 'mobile' : 'desktop'
        );

        if (!in_array($methodType, array_keys($allowedWithdrawalPsps))) {
            return true;
        }

        if (in_array($psp, $this->casinoCashier->getBankSuppliers())) {
            $applicableMethods = phive('Licensed')->getLicSetting('bank_closed_loop_methods', $user->getCountry());
            return empty($applicableMethods) || !in_array($psp, $applicableMethods);
        }

        return false;
    }

    public function resolveTransactionIdentifier(string $psp, string $scheme, string $card): string
    {
        /** @TODO should be handled via CasinoCashier config. */
        if ($scheme === 'googlepay') {
            return $scheme;
        }

        $subSupplierList = $this->casinoCashier->getSubSupplierList();
        $creditCardPsps = $this->casinoCashier->getSetting('ccard_psps');

        if (empty($scheme) && in_array($card, $subSupplierList)) {
            $scheme = $card;
            $card = '';
        }

        if (array_key_exists($psp, $creditCardPsps) && $this->casinoCashier->isCardHash($scheme)) {
            $card = $scheme;
        }

        return !empty($card) ? $card : (in_array($scheme, $subSupplierList) ? $scheme : $psp);
    }

    public function setClosedLoopStartTimestamp(DBUser $user, int $depositId): void
    {
        if (
            $this->casinoCashier->getAntiFraudSchemeByConfig($user) !== 'closed_loop' ||
            $user->hasSetting('closed_loop_start_stamp')
        ) {
            return;
        }

        $deposit = $this->casinoCashier->getDeposit($depositId, $user->getId());
        if (!$deposit) {
            return;
        }

        $identifier = $this->resolveTransactionIdentifier($deposit['dep_type'], $deposit['scheme'], $deposit['card_hash']);

        $depositOnlyLoopConfig = $this->getDepositOnlyClosedLoopConfig($user);
        $isDepositOnlyLoopMethod = !empty($depositOnlyLoopConfig) &&
            (isset($depositOnlyLoopConfig[$identifier]) || isset($depositOnlyLoopConfig[$deposit['scheme']]));

        $shouldSkipClosedLoop = $this->skipClosedLoop($user, $deposit['dep_type'], $identifier);

        if ($isDepositOnlyLoopMethod || !$shouldSkipClosedLoop) {
            $user->setSetting('closed_loop_start_stamp', $deposit['timestamp']);
            $user->deleteSetting('closed_loop_cleared');

            phive('UserHandler')->logAction(
                $user,
                "Set closed loop start stamp to: {$deposit['timestamp']} because none existed before.",
                'closed_loop_start'
            );
        }
    }

    public function getDepositOnlyClosedLoopConfig(DBUser $user): array
    {
        if (isPNP()) {
            return [];
        }

        return phive('Licensed')
            ->getLicSetting('enforce_closed_loop_via_other_psp_for_deposit_only_methods', $user->getCountry()) ?? [];
    }

    public function calculateOverrideRemainingClAmount(array $sourceLoopData, DBUser $user): float
    {
        $overrideRemainingClAmount = !phiveApp(PspConfigServiceInterface::class)->checkLowerLimit(
            $user,
            (int) $sourceLoopData['remaining_amount'],
            $sourceLoopData['source_psp'],
            'out'
        );

        if ($overrideRemainingClAmount) {
            return mc(phiveApp(PspConfigServiceInterface::class)->getLowerLimit(
                'out',
                $sourceLoopData['source_psp'],
                $user
            ), $user);
        }

        return $sourceLoopData['remaining_amount'];
    }
}
