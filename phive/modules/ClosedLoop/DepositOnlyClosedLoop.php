<?php

namespace ClosedLoop;

use CasinoCashier;
use DBUser;

class DepositOnlyClosedLoop
{
    private CasinoCashier $casinoCashier;
    private StandardClosedLoop $standardClosedLoop;

    public function __construct(
        CasinoCashier      $casinoCashier,
        StandardClosedLoop $standardClosedLoop
    )
    {
        $this->casinoCashier = $casinoCashier;
        $this->standardClosedLoop = $standardClosedLoop;
    }

    public function process(DBUser $user, array $closedLoopData): array
    {
        foreach ($this->standardClosedLoop->getDepositOnlyLoopApplicableMethods() as $addedToClosedLoopOfMethod => $depositViaMethods) {
            $targetMethodLoops = $closedLoopData[$addedToClosedLoopOfMethod] ?? [];

            // Check for any previous deposits or withdrawals and use associated account if available.
            $accountToAddDepositOnlyLoop = $this->getAccountToAddDepositOnlyClosedLoop($targetMethodLoops);

            /*
             * If there is no deposit or withdrawal available for the `$addedToClosedLoopOfMethod` in the loop data,
             * we will check for the most recently added account from documents and populate it into closedLoopData
             * to use it, if available.
            */
            if (!$accountToAddDepositOnlyLoop) {
                $latestAddedAccountDocument = array_reduce(
                    $this->getDocumentsForMethod($user, $addedToClosedLoopOfMethod),
                    fn($currentItem, $nextItem) => $currentItem['created_at'] > $nextItem['created_at'] ? $currentItem : $nextItem
                );

                if (!$latestAddedAccountDocument) {
                    continue;
                }

                $accountToAddDepositOnlyLoop = $latestAddedAccountDocument['subtag'];
                $closedLoopData = $this->standardClosedLoop->initializeLoopStructure(
                    $closedLoopData,
                    $latestAddedAccountDocument['supplier'],
                    $accountToAddDepositOnlyLoop,
                    $latestAddedAccountDocument['sub_supplier']
                );

                $targetMethodLoops = $closedLoopData[$addedToClosedLoopOfMethod];
            }

            $amountUsed = 0;
            foreach ($targetMethodLoops as $identifier => $data) {
                if ($data['remaining_amount'] < 0 && $identifier !== $accountToAddDepositOnlyLoop) {
                    $absRemainingAmount = abs($data['remaining_amount']);

                    /*
                     * In this case, since no deposit is available for this account, we need to fake the deposit amount
                     * to display it correctly in the closed-loop overview on the admin BO and to accurately calculate
                     * and sum up the loop amounts when a deposit with the same account becomes available.
                    */
                    $this->updateDepositAmounts($closedLoopData[$addedToClosedLoopOfMethod][$identifier], $absRemainingAmount);

                    // Calculating the total amount for deposit-only methods already used in previous withdrawals.
                    $amountUsed += $absRemainingAmount;
                }
            }

            // Summing up the deposit amounts from deposit-only methods into the target account
            foreach ($depositViaMethods as $method) {
                foreach ($closedLoopData[$method] as $data) {
                    $adjustedDepositAmount = $data['total_deposit_amount'] - $amountUsed;
                    $adjustedRemainingAmount = $data['remaining_amount'] - $amountUsed;

                    $this->updateDepositAmounts($closedLoopData[$addedToClosedLoopOfMethod][$accountToAddDepositOnlyLoop], $adjustedDepositAmount);
                    $closedLoopData[$addedToClosedLoopOfMethod][$accountToAddDepositOnlyLoop]['remaining_amount'] += $adjustedRemainingAmount;
                }
            }
        }

        return $closedLoopData;
    }

    public function applicable(): bool
    {
        return !empty($this->standardClosedLoop->getDepositOnlyLoopApplicableMethods());
    }

    public function filterDepositOnlyLoopOptions(
        DBUser $user,
        array  $pspsToDisplay,
        array  $closedLoopData,
        array  $rawClosedLoopData,
        array  $allowedWdPsps = []
    ): array
    {
        $depositOnlyMethods = $this->standardClosedLoop->getDepositOnlyLoopApplicableMethods();
        if (empty($depositOnlyMethods)) {
            return $pspsToDisplay;
        }

        $displayMethodsOnly = [];
        foreach (array_keys($depositOnlyMethods) as $method) {
            if ($this->isDepositOnlyLoopClosedForMethod($method, $rawClosedLoopData) && $this->getDocumentsForMethod($user, $method)) {
                continue;
            }

            if (empty($closedLoopData)) {
                if (isset($pspsToDisplay[$method])) {
                    $displayMethodsOnly[$method] = $pspsToDisplay[$method];
                }
            } elseif (isset($allowedWdPsps[$method])) {
                $pspsToDisplay[$method] = $allowedWdPsps[$method];
            }
        }

        return empty($closedLoopData) && !empty($displayMethodsOnly)
            ? $displayMethodsOnly
            : $pspsToDisplay;
    }

    private function isDepositOnlyLoopClosedForMethod(string $addedToClosedLoopOfMethod, array $rawClosedLoopData): bool
    {
        /*
         * Verify whether the accounts involved in withdrawal calculations without corresponding deposits are included
         * in the closed-loop calculation. If the loop is either unavailable or already closed,
         * the options will not be displayed.
        */
        $totalDeposits = $totalApprovedWithdrawals = 0;

        foreach ($rawClosedLoopData as $data) {
            if ($data['source_psp'] === $addedToClosedLoopOfMethod) {
                $totalDeposits += $data['total_deposit_amount'];
                $totalApprovedWithdrawals += $data['approved_withdraw_amount'];
            }
        }

        return $totalApprovedWithdrawals >= $totalDeposits;
    }

    private function getDocumentsForMethod(DBUser $user, string $method): array
    {
        return array_filter($this->casinoCashier->getUserDocuments($user), fn($doc) => $doc['supplier'] === $method);
    }

    private function getAccountToAddDepositOnlyClosedLoop(array $data): ?string
    {
        $filteredData = collect($data);

        if ($filteredData->pluck('latest_deposit_created_at')->filter()->isNotEmpty()) {
            $filteredData = $filteredData->sortByDesc('latest_deposit_created_at');
        } elseif ($filteredData->pluck('latest_withdrawal_created_at')->filter()->isNotEmpty()) {
            $filteredData = $filteredData->sortByDesc('latest_withdrawal_created_at');
        } else {
            return null;
        }

        return $filteredData->keys()->first();
    }

    private function updateDepositAmounts(&$loopData, $amount)
    {
        $loopData['total_deposit_amount'] += $amount;
        $loopData['rerouted_deposit_amount'] += abs($amount);
    }
}
