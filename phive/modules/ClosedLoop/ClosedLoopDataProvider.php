<?php

namespace ClosedLoop;

use CasinoCashier;
use SQL;

class ClosedLoopDataProvider
{
    private SQL $db;
    private CasinoCashier $casinoCashier;
    private ClosedLoopHelper $closedLoopHelper;

    private string $timeStampRange;

    public function __construct(
        CasinoCashier    $casinoCashier,
        ClosedLoopHelper $closedLoopHelper,
        string           $loopStartTime,
        ?string          $loopEndTime = null
    )
    {
        $this->db = phive('SQL');
        $this->casinoCashier = $casinoCashier;
        $this->closedLoopHelper = $closedLoopHelper;

        $this->timeStampRange = $this->db->tRng($loopStartTime, $loopEndTime ?? phive()->hisNow(), 'timestamp');
    }

    public function getDepositsGroupedBySource(int $userId): array
    {
        $depositData = $this->getDepositsBySource($userId);
        return $this->groupTransactionsBySource($depositData);
    }

    public function getApprovedWithdrawalsGrouped(int $userId): array
    {
        $withdrawData = $this->getWithdrawalsBySource($userId);
        $groupedData = $this->groupTransactionsBySource($withdrawData);

        $groupedData = array_merge($groupedData, $this->getGroupedBankWithdrawals($userId));
        $groupedData = array_replace($groupedData, $this->getGroupedSwishWithdrawals($userId));

        return $groupedData;
    }

    public function getPendingWithdrawalsGrouped(int $userId): array
    {
        $whereStatus = "AND status NOT IN('approved', 'disapproved')";

        $withdrawData = $this->getWithdrawalsBySource($userId, $whereStatus);
        $groupedData = $this->groupTransactionsBySource($withdrawData);

        $groupedData = array_merge($groupedData, $this->getGroupedBankWithdrawals($userId, $whereStatus));
        $groupedData = array_replace($groupedData, $this->getGroupedSwishWithdrawals($userId, $whereStatus));

        return $groupedData;
    }

    private function getDepositsBySource(int $userId): array
    {
        $sql = "
            SELECT
                scheme,
                card_hash,
                currency,
                dep_type AS psp,
                SUM(amount) AS amount,
                SUM(real_cost) AS real_cost,
                SUM(deducted_amount) AS deducted_amount,
                MAX(timestamp) AS latest_timestamp
            FROM
                deposits
            WHERE
                user_id = $userId
                AND status = 'approved'
                $this->timeStampRange
            GROUP BY
                psp, scheme, card_hash
        ";

        return $this->db->sh($userId)->loadArray($sql, 'ASSOC', ['psp', 'scheme', 'card_hash'], '|');
    }

    private function getWithdrawalsBySource(int $userId, string $where_status = "AND status = 'approved'"): array
    {
        $bank_psps = $this->db->makeIn($this->casinoCashier->getBankSuppliers());

        // TODO: "AND payment_method NOT IN ($bank_psps, 'swish')" To be handled in BAN-12402
        $sql = "
            SELECT
                scheme,
                wallet,
                currency,
                payment_method AS psp,
                SUM(amount) AS amount,
                SUM(real_cost) AS real_cost,
                SUM(deducted_amount) AS deducted_amount,
                MAX(timestamp) AS latest_timestamp
            FROM
                pending_withdrawals
            WHERE
                user_id = $userId
                AND payment_method NOT IN ($bank_psps, 'swish')
                $where_status
                $this->timeStampRange
            GROUP BY
                psp, wallet, scheme
        ";

        return $this->db->sh($userId)->loadArray($sql, 'ASSOC', ['psp', 'wallet', 'scheme'], '|');
    }

    private function getGroupedBankWithdrawals(int $userId, string $where_status = "AND status = 'approved'"): array
    {
        $bank_psps = $this->db->makeIn($this->casinoCashier->getBankSuppliers());

        $sql = "
            SELECT
                bank_account_number AS ban,
                SUM(amount) AS amount,
                payment_method AS psp,
                bank_name,
                MAX(timestamp) AS latest_timestamp
            FROM
                pending_withdrawals
            WHERE
                user_id = $userId
                AND payment_method IN ($bank_psps)
                $where_status
                $this->timeStampRange
            GROUP BY
                psp, bank_name, ban
        ";

        return $this->db->sh($userId)->loadArray($sql, 'ASSOC', 'ban');
    }

    private function getGroupedSwishWithdrawals(int $userId, string $where_status = "AND status = 'approved'"): array
    {
        $sql = "
            SELECT
                net_account AS mobile,
                SUM(amount) AS amount,
                payment_method AS psp,
                'trustly' AS scheme
            FROM
                pending_withdrawals
            WHERE
                user_id = $userId
                AND payment_method = 'swish'
                $where_status
                $this->timeStampRange
            GROUP BY
                psp, mobile
        ";

        return $this->db->sh($userId)->loadArray($sql, 'ASSOC', 'mobile');
    }

    private function groupTransactionsBySource(array $transactionsData): array
    {
        $groupedData = [];

        foreach ($transactionsData as $groupedIndex => $tansactionData) {
            list($psp, $scheme, $card) = explode('|', $groupedIndex);

            $target = $this->closedLoopHelper->resolveTransactionIdentifier($psp, $scheme, $card);
            $groupedData[$target] = phive()->addArrays($tansactionData, $groupedData[$target] ?? []);
            $groupedData[$target]['source_col'] = !empty($card) ? 'card_hash' : 'psp';
        }

        return $groupedData;
    }
}
