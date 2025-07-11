<?php

namespace App\Services\Bingo;

use App\Helpers\DownloadHelper;
use App\Helpers\PaginationHelper;
use App\Models\User;
use App\Repositories\BingoRepository;
use Illuminate\Database\Query\Builder;
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

    public function exportBets(
        User    $user,
        Request $request,
        string  $startDate,
        string  $endDate
    ): StreamedResponse
    {
        $records = [];

        $bets = $_SESSION['bingo_user_bets'] ?: $this->getBetsAndWins($request, $user, $startDate, $endDate);

        $records[] = $this->getExportBetListHeader($user);

        foreach ($bets as $bet) {
            $records[] = [
                $bet->bet_id,
                $bet->bet_date,
                $bet->win_id,
                $bet->round_id,
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

    public function getBetsAndWins(
        Request $request,
        User    $user,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $order = null
    ): Collection
    {
        $startDate = $startDate ?: $request->query->get('start_date');
        $endDate = $endDate ?: $request->query->get('end_date');

        return $this->getRepository($request, $user)->getBetsAndWins($startDate, $endDate, $order)->get();
    }

    public function getGameHistoryBetsBuilder(Request $request, User $user): Builder
    {
        return $this->getRepository($request, $user)->getGameHistoryBetsBuilder();
    }

    public function getGameHistoryWinsBuilder(Request $request, User $user): Builder
    {
        return $this->getRepository($request, $user)->getGameHistoryWinsBuilder();
    }

    private function getRepository(Request $request, User $user): BingoRepository
    {
        return new BingoRepository($this->app, $user, $request);
    }

    private function getExportBetListHeader(User $user): array
    {
        return [
            "Bet ID",
            "Bet Date",
            'Win ID',
            'Round ID',
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
        return "{$user->username}-bingo-bets-list_{$startDate}_to_{$endDate}";
    }

    public function getBetDetails(User $user, Request $request, int $bet_id): Collection
    {
        return $this->getRepository($request, $user)->getBetDetails($user, $bet_id)->get();
    }
}
