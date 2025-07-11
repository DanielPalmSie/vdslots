<?php

namespace ClosedLoop;

use DBUser;

class StandardClosedLoop
{
    private ClosedLoopDataProvider $closedLoopDataProvider;
    private ClosedLoopHelper $closedLoopHelper;

    private array $depositOnlyLoopApplicableMethods = [];

    public function __construct(
        ClosedLoopDataProvider $closedLoopDataProvider,
        ClosedLoopHelper       $closedLoopHelper
    )
    {
        $this->closedLoopDataProvider = $closedLoopDataProvider;
        $this->closedLoopHelper = $closedLoopHelper;
    }

    public function getDepositOnlyLoopApplicableMethods(): array
    {
        return $this->depositOnlyLoopApplicableMethods;
    }

    public function process(DBUser $user): array
    {
        // TODO BAN-12013: Refactor the code in the `closedLoopDataProvider` class.
        $depositData = $this->closedLoopDataProvider->getDepositsGroupedBySource($user->getId());
        $approvedWithdrawData = $this->closedLoopDataProvider->getApprovedWithdrawalsGrouped($user->getId());
        $pendingWithdrawData = $this->closedLoopDataProvider->getPendingWithdrawalsGrouped($user->getId());

        $depositOnlyLoopConfig = $this->closedLoopHelper->getDepositOnlyClosedLoopConfig($user);

        $standardClData = [];
        foreach ($depositData as $identifier => $data) {
            if (
                !empty($depositOnlyLoopConfig)
                && (isset($depositOnlyLoopConfig[$identifier]) || isset($depositOnlyLoopConfig[$data['scheme']]))
            ) {
                $this->depositOnlyLoopApplicableMethods[$depositOnlyLoopConfig[$identifier]][] = $identifier;
            }

            $standardClData = $this->processTransactionData($standardClData, $data, $identifier, 'deposit');
        }

        foreach ($approvedWithdrawData as $identifier => $data) {
            $standardClData = $this->processTransactionData($standardClData, $data, $identifier, 'approved_withdraw');
        }

        foreach ($pendingWithdrawData as $identifier => $data) {
            $standardClData = $this->processTransactionData($standardClData, $data, $identifier, 'pending_withdraw');
        }

        return $standardClData;
    }

    public function initializeLoopStructure(
        array   $closedLoopData,
        string  $psp,
        string  $identifier,
        ?string $scheme = null
    ): array
    {
        if (!isset($closedLoopData[$psp][$identifier])) {
            $closedLoopData[$psp][$identifier] = [
                'source_type' => $this->getSourceType($psp, $identifier, $scheme),
                'source_psp' => $psp,
                'source_scheme' => $scheme,
                'deposit_amount' => 0,
                'pending_withdraw_amount' => 0,
                'approved_withdraw_amount' => 0,
                'total_withdraw_amount' => 0, // PendingWd + ApprovedWd
                'remaining_amount' => 0, // Deposit - PendingWd - ApprovedWd
                'rerouted_deposit_amount' => 0,
                'total_deposit_amount' => 0, // Normal + Rerouted
                'latest_deposit_created_at' => null,
                'latest_withdrawal_created_at' => null, // Approved Withdrawal
                'status' => null // -1, 0, 1
            ];
        }

        return $closedLoopData;
    }

    private function processTransactionData(
        array  $standardClData,
        array  $transactionData,
        string $identifier,
        string $transactionType
    ): array
    {
        $psp = $transactionData['psp'];
        $schemeOrBankName = $transactionData['scheme'] ?? $transactionData['bank_name'];

        if (strpos($transactionType, 'withdraw') !== false
            && $transactionData['source_col'] === 'card_hash'
            && !isset($standardClData[$psp][$identifier])
        ) {
            $newPsp = key(array_filter($standardClData, fn($data) => isset($data[$identifier]))) ?: null;
            if ($newPsp && $identifier === $schemeOrBankName) {
                $psp = $newPsp;
            }
        }

        $standardClData = $this->initializeLoopStructure($standardClData, $psp, $identifier, $schemeOrBankName);

        $amountKey = $transactionType . '_amount';
        $timestampKey = $transactionType === 'deposit'
            ? 'latest_deposit_created_at'
            : 'latest_withdrawal_created_at';

        $standardClData[$psp][$identifier][$amountKey] = $transactionData['amount'];

        if ($transactionType === 'deposit') {
            $standardClData[$psp][$identifier]['remaining_amount'] = $transactionData['amount'];
            $standardClData[$psp][$identifier]['total_deposit_amount'] = $transactionData['amount'];
        } else {
            $standardClData[$psp][$identifier]['remaining_amount'] -= $transactionData['amount'];
            $standardClData[$psp][$identifier]['total_withdraw_amount'] += $transactionData['amount'];
        }

        if ($transactionType !== 'pending_withdraw') {
            $standardClData[$psp][$identifier][$timestampKey] = $transactionData['latest_timestamp'];
        }

        return $standardClData;
    }

    private function getSourceType(string $psp, string $identifier, ?string $scheme): string
    {
        $type = $this->closedLoopHelper->getAccountType($psp, $identifier);
        if ($type === 'card') {
            return $scheme === 'applepay' ? 'applepay' : 'card';
        }

        return $type === 'bank' ? 'bank' : 'psp';
    }
}
