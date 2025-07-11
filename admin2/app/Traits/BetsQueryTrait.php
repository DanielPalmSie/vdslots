<?php

namespace App\Traits;

use App\Extensions\Database\ArchiveFManager as ArchiveDB;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Helpers\DateHelper;
use App\Helpers\ValidationHelper;
use App\Models\Bet;
use App\Models\BetMp;
use App\Models\User;
use App\Models\Win;
use App\Models\WinMp;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BetsQueryTrait
 * @package App\Traits
 */
trait BetsQueryTrait
{
    public array $query_data;
    protected User $user;
    protected Request $request;
    protected Builder $bets_query;
    protected Builder $wins_query;
    protected string $bets_table_name;
    protected string $wins_table_name;
    protected Builder $bets_archived_query;
    protected Builder $wins_archived_query;

    /**
     * Processes the bet and win query data.
     *
     * @param bool $archived
     * @return void
     */
    public function processBetWinQueryData(bool $archived = false): void
    {
        $date_range = DateHelper::validateDateRange($this->request, 1);

        $bets_archive_date = $this->isArchiveDate($date_range['start_date'], $this->getLastBetsDate());
        $wins_archive_date = $this->isArchiveDate($date_range['start_date'], $this->getLastWinsDate());

        list($game, $deviceType) = $this->getGameAndDeviceType();

        $this->query_data = $this->getQueryData($date_range, $game, $deviceType);

        $this->addCustomOrderToQueryData();

        $this->setBetAndWinObjects($archived, $bets_archive_date, $wins_archive_date);
    }

    /**
     * Gets the last bets date.
     *
     * @return array
     */
    public function getLastBetsDate(): array
    {
        return DB::shsSelect('misc_cache', "SELECT cache_value FROM misc_cache WHERE id_str = 'node-archive-end-date-bets';");
    }

    /**
     * Gets the last wins date.
     *
     * @return array
     */
    public function getLastWinsDate(): array
    {
        return DB::shsSelect('misc_cache', "SELECT cache_value FROM misc_cache WHERE id_str = 'node-archive-end-date-wins';");
    }

    /**
     * Retrieves and processes the game and device type from the request.
     *
     * @return array
     */
    private function getGameAndDeviceType(): array
    {
        // when the request comes from the CSV button we have device_type passed as a parameter, so we need to match the param like this
        list($game, $deviceType) = explode('|', $this->request->get('game', '|'));

        if (empty($deviceType) && !empty($this->request->get('device_type'))) {
            $deviceType = $this->request->get('device_type');
        }

        return [$game, $deviceType];
    }

    /**
     * Retrieves query data from the request.
     *
     * @param array $date_range
     * @param string|null $game
     * @param string|null $deviceType
     * @return array
     */
    private function getQueryData(array $date_range, ?string $game, ?string $deviceType): array
    {
        return [
            'start_date' => $this->request->get('ext_start_date', $date_range['start_date']),
            'end_date' => $this->request->get('ext_end_date', $date_range['end_date']),
            'bonus' => $this->request->get('bonus'),
            'order' => ValidationHelper::validateOrderBySort($this->request->get('order')),
            'game' => $game,
            'operator' => $this->request->get('operator'),
            // IMPORTANT: when using this filter on "micro_games.device_type" instead of "bets/wins.device_type"
            // the column was not stored properly until 2019-05-09 (Some GP are still getting fixed)
            'device_type' => $deviceType,
            'chrono' => $this->request->get('chrono'),
            'only_trans' => $this->request->get('only_trans'),
            'date-range' => $this->request->get('date-range'),
            'export' => $this->request->get('export'),
            'mp' => $this->request->get('mp'),
            'vertical' => $this->request->get('vertical')
        ];
    }

    /**
     * Adds custom order to the query data if present in the request.
     *
     * @return void
     */
    private function addCustomOrderToQueryData(): void
    {
        if ($customOrder = $this->request->get('custom_order')) {
            $this->query_data['custom_order'] = $customOrder;
        }
    }

    /**
     * Determines if the given start date is before the archive end date.
     *
     * @param string $start_date
     * @param array $last_dates
     * @return bool
     */
    private function isArchiveDate(string $start_date, array $last_dates): bool
    {
        return Carbon::parse($start_date)->lessThanOrEqualTo(Carbon::parse($last_dates[0]->cache_value));
    }

    /**
     * Sets the bet and win objects and queries based on the archived flag and dates.
     *
     * @param bool $archived
     * @param bool $bets_archive_date
     * @param bool $wins_archive_date
     * @return void
     */
    private function setBetAndWinObjects(bool $archived, bool $bets_archive_date, bool $wins_archive_date): void
    {
        if ($this->request->get('mp') == 1) {
            $bets_obj = new BetMp();
            $wins_obj = new WinMp();
        } else {
            $bets_obj = new Bet();
            $wins_obj = new Win();
        }

        $this->bets_table_name = $bets_obj->getTable();
        $this->wins_table_name = $wins_obj->getTable();

        if ($archived === false) {
            $this->bets_query = ReplicaDB::shTable($this->user->getKey(), $this->bets_table_name)->where('user_id', $this->user->getKey());
            $this->wins_query = ReplicaDB::shTable($this->user->getKey(), $this->wins_table_name)->where($this->wins_table_name . '.user_id', $this->user->getKey());
        } elseif ($bets_archive_date && $wins_archive_date) {
            $this->bets_archived_query = ArchiveDB::shTable($this->user->getKey(), $this->bets_table_name)->where('user_id', $this->user->getKey());
            $this->wins_archived_query = ArchiveDB::shTable($this->user->getKey(), $this->wins_table_name)->where($this->wins_table_name . '.user_id', $this->user->getKey());
        } else {
            $this->bets_archived_query = DB::connection('videoslots_archived')->table($bets_obj->getTable())->where('user_id', $this->user->getKey());
            $this->wins_archived_query = DB::connection('videoslots_archived')->table($wins_obj->getTable())->where($this->wins_table_name.'.user_id', $this->user->getKey());
        }
    }

    public function processBasicBetQueryData(?string $startDate = null, ?string $endDate = null, ?string $order = null): array
    {
        // Validate the date range and fetch query data
        $dateRange = DateHelper::validateDateRange($this->request, 1);
        $queryData = $this->getQueryData($dateRange, null, null);

        // Validate and normalize start and end dates
        $startDate = DateHelper::validateStartOfDay($startDate ?? $queryData['start_date'] ?? null);
        $endDate = DateHelper::validateEndOfDay($endDate ?? $queryData['end_date'] ?? null);

        // Set default order if not provided
        $order = $order ?? $queryData['order'] ?? 'DESC';

        return [$startDate, $endDate, $order];
    }

}
