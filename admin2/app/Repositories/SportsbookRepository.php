<?php

namespace App\Repositories;

use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use Illuminate\Support\Collection;

class SportsbookRepository
{
    public function getLatestSportTransaction(int $userId, int $ticketId): object
    {
        $res = $this->getTicketSportTransaction($userId, $ticketId)
            ->sortByDesc('id')
            ->first();

        return $res;
    }

    public function getTicketSportTransaction(int $userId, int $ticketId): Collection
    {
        return ReplicaDB::shTable($userId, 'sport_transactions')
            ->where('user_id', $userId)
            ->where('ticket_id', $ticketId)
            ->get();
    }
}
