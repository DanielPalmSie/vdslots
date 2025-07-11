<?php

declare(strict_types=1);

namespace Videoslots\User\Sportsbook;

use Laraphive\Domain\User\DataTransferObjects\Requests\GetSportsHistoryRequestData;
use Laraphive\Domain\User\DataTransferObjects\Responses\SportsHistoryResponseData;
use Laraphive\Domain\User\DataTransferObjects\SportsHistoryData;
use Laraphive\Domain\User\DataTransferObjects\SportsTransactionData;

final class SportsHistoryService
{
    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\Requests\GetSportsHistoryRequestData $data
     * @return \Laraphive\Domain\User\DataTransferObjects\Responses\SportsHistoryResponseData
     */
    public function getSportsTickets(GetSportsHistoryRequestData $data): SportsHistoryResponseData
    {
        $userId = $data->getUserId();

        $whereClause = $this->buildWhereClause($data);

        $transactionsQuery = $this->builTransactionsQuery(
            $whereClause,
            $data->getLimit(),
            $data->getOffset()
        );
        $transactions = phive('SQL')->sh($userId)->loadArray($transactionsQuery);

        $totalQuery = $this->buildTotalQuery($whereClause);
        $total = (int) phive('SQL')->sh($userId)->getValue($totalQuery);

        return new SportsHistoryResponseData(
            $this->formatTicketData($transactions),
            $total
        );
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\Requests\GetSportsHistoryRequestData $data
     * @return string
     */
    private function builTransactionsQuery(string $whereClause, int $limit, int $offset): string
    {
        return "SELECT tr.*
            FROM sport_transactions tr
            JOIN (
                SELECT ticket_id
                FROM sport_transactions
                WHERE {$whereClause}
                GROUP BY ticket_id
                ORDER BY created_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ) AS tickets ON tr.ticket_id = tickets.ticket_id
            ORDER BY tr.created_at DESC;";
    }

    /**
     * @param string $whereClause
     * @return string
     */
    private function buildTotalQuery(string $whereClause): string
    {
        return "SELECT COUNT(DISTINCT ticket_id)
            FROM sport_transactions
            WHERE {$whereClause};";
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\Requests\GetSportsHistoryRequestData $data
     * @return string
     */
    private function buildWhereClause(GetSportsHistoryRequestData $data): string
    {
        $conditions = [
            "product = 'S'",
            "user_id = {$data->getUserId()}",
        ];

        if ($data->getStartDate() !== null) {
            $conditions[] = "created_at >= '{$data->getStartDate()}'";
        }
        if ($data->getEndDate() !== null) {
            $conditions[] = "created_at <= '{$data->getEndDate()}'";
        }

        return implode(" AND ", $conditions);
    }

    /**
     * @param array $transactions
     * @return \Laraphive\Domain\User\DataTransferObjects\SportsHistoryData[]
     */
    private function formatTicketData(array $transactions): array
    {
        $ticketIds = array_unique(array_column($transactions, 'ticket_id'));

        /**
         * @var \Laraphive\Domain\User\DataTransferObjects\SportsHistoryData[]
         */
        $tickets = [];
        foreach ($ticketIds as $ticketId) {
            $ticketTransactions = array_filter($transactions, function ($transaction) use ($ticketId) {
                return $transaction['ticket_id'] === $ticketId;
            });

            /**
             * @var \Laraphive\Domain\User\DataTransferObjects\SportsTransactionData[]
             */
            $parsedTransactions = [];
            foreach ($ticketTransactions as $transaction) {
                $parsedTransactions[] = new SportsTransactionData($transaction);
            }

            usort($parsedTransactions, function ($a, $b) {
                return $a->getCreatedAt() <=> $b->getCreatedAt();
            });
            $tickets[] = new SportsHistoryData((int) $ticketId, $parsedTransactions);
        }

        return $tickets;
    }
}