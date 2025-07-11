<?php

namespace App\Repositories;

use App\Constants\Networks;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Models\User;
use App\Traits\BetsQueryTrait;
use Illuminate\Database\Query\Builder;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class BingoRepository
{
    use BetsQueryTrait;

    private Application $app;

    public function __construct(Application $app, User $user, Request $request)
    {
        $this->app = $app;
        $this->user = $user;
        $this->request = $request;
    }

    public function getBetsAndWins(?string $startDate = null, ?string $endDate = null, ?string $order = null): Builder
    {
        return $this->getBetsWinsCommon(null, $startDate, $endDate, $order);
    }

    public function getBets(?string $startDate = null, ?string $endDate = null, ?string $order = null): Builder
    {
        return $this->getBetsWinsCommon('bet', $startDate, $endDate, $order);
    }

    public function getWins(?string $startDate = null, ?string $endDate = null, ?string $order = null): Builder
    {
        return $this->getBetsWinsCommon('win', $startDate, $endDate, $order);
    }

    private function getBetsWinsCommon(?string $betType = null, ?string $startDate = null, ?string $endDate = null, ?string $order = null): Builder
    {
        [$startDate, $endDate, $order] = $this->processBasicBetQueryData($startDate, $endDate, $order);

        $subQuery = ReplicaDB::shTable($this->user->getKey(), 'sport_transactions as spt')
            ->select(
                'ticket_id',
                ReplicaDB::raw('MAX(id) as latest_id'),
                ReplicaDB::raw('MIN(id) as earliest_id')
            )
            ->groupBy('ticket_id');

        $data = ReplicaDB::shTable($this->user->getKey(), 'sport_transactions as mst')
            ->joinSub($subQuery, 'transactions', function ($join) {
                $join->on('mst.ticket_id', '=', 'transactions.ticket_id')
                    ->on('mst.id', '=', 'transactions.latest_id');
            })
            ->join('sport_transactions as first_mst', function ($join) {
                $join->on('mst.ticket_id', '=', 'first_mst.ticket_id')
                    ->on('first_mst.id', '=', 'transactions.earliest_id');
            })
            ->leftJoin('sport_transaction_info as sti', 'mst.id', '=', 'sti.sport_transaction_id')
            ->select(
                'mst.user_id',
                'mst.ticket_id as bet_id',
                'mst.id as transaction_id',
                'mst.ext_id as ext_transaction_id',
                'mst.ticket_settled',
                'mst.ticket_type',
                'mst.bet_type as type',
                'mst.amount',
                'first_mst.bet_placed_at as bet_date',
                'mst.balance as end_balance',
                ReplicaDB::raw("IF(mst.bet_type = 'win', mst.id, NULL) as win_id"),
                ReplicaDB::raw("IF(mst.bet_type = 'win', mst.amount, NULL) as win_amount"),
                ReplicaDB::raw("first_mst.amount as bet_amount"),
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.round_id")) as round_id')
            )
            ->where('mst.user_id', $this->user->getKey())
            ->where('mst.product', Networks::BINGO['product'])
            ->when($betType, function ($query) use ($betType) {
                $query->where('mst.bet_type', $betType);
            })
            ->whereBetween('mst.created_at', [$startDate, $endDate])
            ->orderBy('mst.created_at', $order)
            ->groupBy('sti.sport_transaction_id');

        if (!$betType) {
            $_SESSION['bingo_user_bets'] = $data->get();
        }

        return $data;
    }

    public function getBetDetails(User $user, int $betId): Builder
    {
        $userId = $user->getKey();

        return ReplicaDB::shTable($userId, 'sport_transactions as st')
            ->leftJoin('sport_transaction_info as sti', 'st.id', '=', 'sti.sport_transaction_id')
            ->select(
                'st.*',
                'st.ticket_id as bet_id',
                'st.ticket_type as type',
                'st.balance as user_balance',
                'sti.transaction_type as transaction_type',
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.transaction_id")) as ext_transaction_id')
            )
            ->where('st.product', Networks::BINGO['product'])
            ->where('st.user_id', $userId)
            ->where('st.ticket_id', $betId)
            ->orderBy('st.created_at');
    }

    public function getGameHistoryBetsBuilder(): Builder
    {
        return $this->getGameHistoryBetsWinsCommonBuilder('bet');
    }

    public function getGameHistoryWinsBuilder(): Builder
    {
        return $this->getGameHistoryBetsWinsCommonBuilder('win');
    }

    private function getGameHistoryBetsWinsCommonBuilder(?string $betType = null): Builder
    {
        return ReplicaDB::shTable($this->user->getKey(), 'sport_transactions as st')
            ->leftJoin('sport_transaction_info as sti', 'st.id', '=', 'sti.sport_transaction_id')
            ->select(
                'st.created_at',
                'st.currency',
                'st.amount',
                ReplicaDB::raw('JSON_UNQUOTE(JSON_EXTRACT(sti.json_data, "$.game_id")) as game_name')
            )
            ->when($betType, function ($query) use ($betType) {
                $query->where('st.bet_type', $betType);
            })
            ->where('st.product', Networks::BINGO['product'])
            ->where('st.user_id', $this->user->getKey());
    }
}
