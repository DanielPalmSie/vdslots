<?php

namespace GamesRecommendations\Traits;

use DateTime;
use Exception;

trait DataCollectionTrait
{
    abstract protected function getBrandId();

    /**
     * Collect bets data
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return bool
     */
    private function collectBetsData(DateTime $startDate, DateTime $endDate): bool
    {
        $csv_file = $this->getFilePath(self::DATA_FILES['bets']);
        $file = fopen($csv_file, 'w');

        if (!$file) {
            return false;
        }

        // Write headers
        fputcsv($file, [
            'transaction_date', 'player_id', 'brand_id', 'game_provider_id', 'game_provider_name',
            'game_id', 'game_name', 'game_count', 'bet_count', 'turnover', 'turnover_real', 'ggr',
            'ggr_real', 'currency'
        ]);

        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate);

        foreach ($period as $date) {
            $query = $this->getBetsQuery($date);
            $result = phive('SQL')->shs()->loadArray($query);

            foreach ($result as $row) {
                fputcsv($file, [
                    $row['transaction_date'],
                    $row['player_id'],
                    $row['brand_id'],
                    $row['game_provider_id'],
                    $row['game_provider_name'],
                    $row['game_id'],
                    $row['game_name'],
                    $row['game_count'],
                    $row['bet_count'],
                    $row['turnover'],
                    $row['turnover_real'],
                    $row['ggr'],
                    $row['ggr_real'],
                    $row['currency']
                ]);
            }
        }

        fclose($file);
        return true;
    }

    /**
     * Collect games data
     * @return bool
     */
    private function collectGamesData(): bool
    {
        $csv_file = $this->getFilePath(self::DATA_FILES['games']);
        $file = fopen($csv_file, 'w');

        if (!$file) {
            return false;
        }

        fputcsv($file, ['brand_id', 'game_id', 'game_name', 'game_provider_id', 'game_provider_name', 'device']);

        $query = $this->getGamesQuery();
        $result = phive('SQL')->loadArray($query);

        foreach ($result as $row) {
            fputcsv($file, [
                $row['brand_id'],
                $row['game_id'],
                $row['game_name'],
                $row['game_provider_id'],
                $row['game_provider_name'],
                $row['device']
            ]);
        }

        fclose($file);
        return true;
    }

    /**
     * Collect players data
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return bool
     */
    private function collectPlayersData(DateTime $startDate, DateTime $endDate): bool
    {
        $csv_file = $this->getFilePath(self::DATA_FILES['players']);
        $file = fopen($csv_file, 'w');

        if (!$file) {
            return false;
        }

        fputcsv($file, ['player_id', 'brand_id', 'player_reg_date', 'player_country', 'language']);

        $query = $this->getPlayersQuery($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $result = phive('SQL')->shs()->loadArray($query);

        foreach ($result as $row) {
            fputcsv($file, [
                $row['player_id'],
                $row['brand_id'],
                $row['player_reg_date'],
                $row['player_country'],
                $row['language_iso639_2']
            ]);
        }

        fclose($file);
        return true;
    }

    /**
     * Get bets query
     * @param DateTime $date
     * @return string
     */
    private function getBetsQuery(DateTime $date): string
    {
        return "SELECT
            a.transaction_date
            , a.player_id
            , '" . $this->getBrandId() . "' brand_id
            , mg.network game_provider_id
            , mg.network game_provider_name
            , a.game_ref game_id
            , TRIM(mg.game_name) game_name
            , sum(a.game_count) game_count
            , sum(a.bet_count) bet_count
            , round(sum(a.turnover)/100,2) turnover
            , round(sum(a.turnover_real)/100,2) turnover_real
            , round(sum(a.ggr)/100,2) ggr
            , round(sum(a.ggr_real)/100,2) ggr_real
            , a.currency
        FROM (
            SELECT
                date_format(udgs.`date`,'%Y-%m-%d 00:00:00') transaction_date
                , udgs.user_id player_id
                , udgs.game_ref
                , udgs.device_type
                , udgs.currency
                , sum(udgs.bets_count) game_count
                , sum(udgs.bets_count) bet_count
                , sum(udgs.bets) turnover
                , sum(udgs.bets) turnover_real
                , sum(udgs.bets) - sum(udgs.wins + udgs.frb_wins) ggr
                , sum(udgs.bets) - sum(udgs.wins) ggr_real
            FROM users_daily_game_stats udgs
            WHERE udgs.`date` BETWEEN '{$date->format('Y-m-d')} 00:00:00' AND '{$date->format('Y-m-d')} 23:59:59'
            AND udgs.`game_ref` <> ''
            GROUP BY
                date_format(udgs.`date`,'%Y-%m-%d 00:00:00')
                , udgs.user_id
                , udgs.game_ref
                , udgs.device_type
                , udgs.currency
        ) a
        INNER JOIN micro_games mg ON a.game_ref = mg.ext_game_name AND a.device_type = mg.device_type_num
        WHERE mg.game_name <> ''
            AND mg.active = 1
            AND mg.retired = 0
            AND mg.enabled = 1
        GROUP BY
            a.transaction_date
            , a.player_id
            , mg.network
            , a.game_ref
            , mg.game_name
            , a.currency
        ORDER BY a.transaction_date ASC";
    }

    /**
     *  We are adding the games to the zingbrain model as desktop, mobile or both, since in our system they have duplicated game ids for desktop and mobile
     *  so in our micro_games table for a single game, we will have 2 entries,  one for the mobile and another for the desktop version
     *  but on zingbrain system it will be only one entry with device set as 'both'
     *
     * @return string
     */
    private function getGamesQuery(): string
    {
        return "SELECT
            '".$this->getBrandId()."' brand_id,
            a.ext_game_name game_id,
            a.game_name,
            a.network game_provider_id,
            a.network game_provider_name,
            CASE
                WHEN a.device = '0' THEN 'desktop'
                WHEN a.device != '0' AND NOT a.device LIKE '%,%' THEN 'mobile'
                ELSE
                    CASE
                        WHEN a.device LIKE '%0%' THEN 'both'
                        ELSE 'mobile'
                    END
            END device
        FROM (
            SELECT
                mg.ext_game_name,
                mg.game_name,
                mg.network,
                CASE mg.operator WHEN '' THEN mg.network ELSE mg.operator END operator,
                group_concat(mg.device_type_num) device
            FROM (
                SELECT DISTINCT
                    mg.ext_game_name,
                    mg.game_name,
                    mg.network,
                    mg.operator,
                    mg.device_type_num
                FROM micro_games mg
                WHERE mg.active = 1 AND mg.retired = 0 AND mg.enabled = 1
            ) mg
            GROUP BY
                mg.ext_game_name,
                mg.network
        ) a
        WHERE a.ext_game_name <> ''";
    }

    /**
     * Get players query
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    private function getPlayersQuery(string $startDate, string $endDate): string
    {
        return "SELECT DISTINCT
            a.player_id
            , a.brand_id
            , a.player_reg_date
            , CASE WHEN bc.iso3 = 'ALA' THEN 'FIN' ELSE bc.iso3 END player_country
            , CASE
                WHEN a.`language` = 'br' THEN 'bre'
                WHEN a.`language` = 'cl' THEN 'spa'
                WHEN a.`language` = 'da' THEN 'dan'
                WHEN a.`language` = 'de' THEN 'deu'
                WHEN a.`language` = 'dgoj' THEN 'spa'
                WHEN a.`language` = 'en' THEN 'eng'
                WHEN a.`language` = 'es' THEN 'spa'
                WHEN a.`language` = 'fi' THEN 'fin'
                WHEN a.`language` = 'it' THEN 'ita'
                WHEN a.`language` = 'nl' THEN 'nld'
                WHEN a.`language` = 'no' THEN 'nor'
                WHEN a.`language` = 'on' THEN 'eng'
                WHEN a.`language` = 'pe' THEN 'spa'
                WHEN a.`language` = 'sv' THEN 'swe'
            ELSE a.`language`
            END language_iso639_2
        FROM (
            SELECT
                u.id player_id
                , '" . $this->getBrandId() . "' brand_id
                , COALESCE(us.value,DATE_FORMAT(u.register_date,'%Y-%m-%d 00:00:00')) player_reg_date
                , u.country player_country
                , UPPER(u.preferred_lang) `language`
            FROM users u
            INNER JOIN users_settings us ON u.id = us.user_id AND us.setting = 'registration_end_date'
            WHERE u.register_date BETWEEN '$startDate' AND '$endDate'
            UNION ALL
            SELECT
                u.id player_id
                , '" . $this->getBrandId() . "' brand_id
                , COALESCE(us.value,DATE_FORMAT(u.register_date,'%Y-%m-%d 00:00:00')) player_reg_date
                , u.country player_country
                , UPPER(u.preferred_lang) `language`
            FROM users u
            LEFT JOIN users_settings us ON u.id = us.user_id AND us.setting = 'registration_end_date'
            WHERE u.id IN (SELECT DISTINCT user_id FROM users_daily_game_stats WHERE date BETWEEN '$startDate' AND '$endDate')
        ) a
        JOIN bank_countries bc ON a.player_country = bc.iso COLLATE utf8_unicode_ci";
    }

    /**
     * Clean up old CSV files
     * @return void
     */
    private function cleanupFiles(): void
    {
        $files = ['bets.csv', 'actual-game-list.csv', 'players.csv'];
        foreach ($files as $file) {
            $filePath = $this->getFilePath($file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    private function getFilePath($file) {
        return sys_get_temp_dir().'/'.$file;
    }
}
