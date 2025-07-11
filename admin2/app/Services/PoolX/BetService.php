<?php

namespace App\Services\PoolX;

use App\Helpers\DownloadHelper;
use App\Models\User;
use App\Repositories\PoolXRepository;
use Illuminate\Support\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BetService
{
    private Application $app;
    private const STATUS_BET = 'bet';
    private const STATUS_LOSS = 'loss';

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function exportPoolXBets(
        User    $user,
        Request $request,
        string  $startDate,
        string  $endDate
    ): StreamedResponse
    {
        $records = [];

        $poolXBets = $_SESSION['poolx_user_bets'] ?: $this->getPoolXBets($request, $user, $startDate, $endDate);

        $records[] = $this->getExportBetListHeader($user);

        foreach ($poolXBets as $bet) {
            $records[] = [
                $bet->bet_id,
                $bet->bet_date,
                $bet->win_id,
                $bet->round_id,
                $bet->player_round_id,
                $bet->transaction_id,
                $bet->ext_transaction_id,
                $this->resolveBetStatus($bet->type, $bet->ticket_settled),
                $this->formatAmount($bet->bet_amount),
                $this->formatAmount($bet->win_amount),
                $this->formatAmount($bet->end_balance)
            ];
        }

        return DownloadHelper::streamAsCsv(
            $this->app,
            $records,
            $this->getExportFileName($user, $startDate, $endDate)
        );
    }

    public function getPoolXBets(
        Request $request,
        User    $user,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $order = null
    ): Collection
    {
        $startDate = $startDate ?: $request->query->get('start_date');
        $endDate = $endDate ?: $request->query->get('end_date');

        return $this->getPoolXRepository($request, $user)->getPoolXBets($startDate, $endDate, $order)->get();
    }

    private function getPoolXRepository(Request $request, User $user): PoolXRepository
    {
        return new PoolXRepository($this->app, $user, $request);
    }

    private function getExportBetListHeader(User $user): array
    {
        return [
            "Bet ID",
            "Bet Date",
            'Win ID',
            "Round ID",
            "Player Round ID",
            "Transaction ID",
            "Ext Transaction ID",
            "Type",
            "Bet Amount ({$user->currency})",
            "Actual Win Amount ({$user->currency})",
            "End Balance",
        ];
    }

    private function resolveBetStatus(string $type, ?string $ticketSettled): string
    {
        if (empty($ticketSettled)) {
            return $type;
        }

        return $type === self::STATUS_BET ? self::STATUS_LOSS : $type;
    }

    private function formatAmount(?int $amount = 0): int
    {
        return $amount / 100;
    }

    private function getExportFileName(User $user, string $startDate, string $endDate): string
    {
        return "{$user->username}-poolX-bets-list_{$startDate}_to_{$endDate}";
    }

    public function getPoolXBetDetails(User $user, Request $request, int $bet_id): Collection
    {
        return $this->getPoolXRepository($request, $user)->getPoolXBetDetails($user, $bet_id)->get();
    }

}
