<?php

namespace App\Repositories;

use App\Constants\Networks;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Helpers\DateHelper;
use App\Models\User;
use App\Traits\BetsQueryTrait;
use Illuminate\Database\Query\Builder;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class PoolXRepository
{
    use BetsQueryTrait;

    private Application $app;

    public function __construct(Application $app, User $user, Request $request)
    {
        $this->app = $app;
        $this->user = $user;
        $this->request = $request;
    }

    public function getPoolXBets(?string $startDate = null, ?string $endDate = null, ?string $order = null): Builder
    {
        [$startDate, $endDate, $order] = $this->processBasicBetQueryData($startDate, $endDate, $order);

        $subQuery = ReplicaDB::shTable($this->user->getKey(), 'sport_transactions as spt')
            ->select(
                'ticket_id',
                ReplicaDB::raw('MAX(id) as latest_id'),
                ReplicaDB::raw('MIN(id) as earliest_id')
            )
            ->groupBy('ticket_id');

        // Main query to join with the latest and earliest records
        $poolXBets = ReplicaDB::shTable($this->user->getKey(), 'sport_transactions as mst')
            ->joinSub($subQuery, 'transactions', function ($join) {
                $join->on('mst.ticket_id', '=', 'transactions.ticket_id')
                    ->on('mst.id', '=', 'transactions.latest_id');
            })
            // Join with the earliest record to get its details
            ->join('sport_transactions as first_mst', function ($join) {
                $join->on('mst.ticket_id', '=', 'first_mst.ticket_id')
                    ->on('first_mst.id', '=', 'transactions.earliest_id');
            })
            // Join with the sport_transaction_info table to get json_data
            ->leftJoin('sport_transaction_info as sti', 'mst.id', '=', 'sti.sport_transaction_id')
            ->groupBy('sti.sport_transaction_id')

            ->select(
                'mst.user_id as user_id',
                'mst.ticket_id as bet_id',
                'mst.id as transaction_id',
                'mst.ext_id as ext_transaction_id',
                'mst.ticket_settled as ticket_settled',
                'mst.ticket_type as ticket_type',
                'mst.bet_type as type',
                'mst.amount as amount',
                'mst.created_at as bet_date',
                'mst.balance as end_balance',
                ReplicaDB::raw("CASE WHEN mst.bet_type = 'win' THEN mst.id ELSE NULL END as win_id"),
                ReplicaDB::raw("CASE WHEN mst.bet_type = 'win' THEN mst.amount ELSE NULL END as win_amount"),
                ReplicaDB::raw('first_mst.amount as bet_amount'),
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.roundid")) as round_id'),
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.playerroundid")) as player_round_id')
            )
            ->where('mst.user_id', $this->user->getKey())
            ->where('mst.product', Networks::POOLX['product'])
            ->where('mst.network', Networks::POOLX['name'])
            ->whereBetween('mst.created_at', [$startDate, $endDate])
            ->orderBy('mst.created_at', $order);

        $_SESSION['poolx_user_bets'] = $poolXBets->get();

        return $poolXBets;
    }

    public function getPoolXBetDetails(User $user, int $betId): Builder
    {
        $userId = $user->getKey();

        return ReplicaDB::shTable($userId, 'sport_transactions as st')
            ->leftJoin('sport_transaction_info as sti', 'st.id', '=', 'sti.sport_transaction_id')
            ->select(
                'st.*',
                'st.ticket_id as bet_id',
                'st.ticket_type as type',
                'st.balance as user_balance',
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.roundid")) as round_id'),
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.playerroundid")) as player_round_id'),
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.transactionid")) as ext_transaction_id'),
            )
            ->where('st.product', Networks::POOLX['product'])
            ->where('st.network', Networks::POOLX['name'])
            ->where('sti.network', Networks::POOLX['name'])
            ->where('st.user_id', $userId)
            ->where('st.ticket_id', $betId)
            ->orderBy('st.created_at');
    }
}
