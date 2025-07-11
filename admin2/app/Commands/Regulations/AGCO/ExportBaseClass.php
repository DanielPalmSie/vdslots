<?php

namespace App\Commands\Regulations\AGCO;

use App\Extensions\Database\Builder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\ReplicaFManager as DB;
use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;
use Exception;

abstract class ExportBaseClass extends Command
{
    protected const BRAND_VIDEOSLOTS = 'videoslots';
    protected const BRAND_MRVEGAS = 'mrvegas';
    protected const STORAGE_PATH = 'AGCO';
    protected const DST_INTERVAL_TIME = '4';
    protected const INTERVAL_TIME = '5';
    protected string $start_date;
    protected string $end_date;
    protected string $brand;
    protected string $gaming_site_id;
    protected array $data = [];
    protected array $headers = [];
    protected array $csv = [];
    protected string $product_code = 'Casino';
    protected string $currency = 'CAD';
    protected string $file_path;
    protected string $file_name;
    protected $sql;
    protected ?Connection $connection;
    protected Carbon $latest_timezone_change_date;
    protected $app;
    private string $log_info = '';
    /**
     * @var bool|object|\Phive
     */
    private $license;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getSilexApplication();
        $this->log_info = "Ran: " . json_encode($input->getArguments());
        $this->app['monolog']->addInfo($this->log_info);
        $this->license = phive('Licensed/CA/CAON');

        if (
            strtotime($input->getArgument('start_date')) === false ||
            strtotime($input->getArgument('end_date')) === false ||
            !array_key_exists($input->getArgument('brand'), $this->license->getLicSetting('game_site_ids'))) {
            $output->writeln("Please insert the correct arguments for the function");
            $this->app['monolog']->addError($this->log_info . " failed cause of wrong input arguments");

            return 0;
        }


        try {
            $this->init($input, $output);
            $this->sql = phive('SQL');
            $this->collectDataFromShardTables();
            $this->app['monolog']->addInfo($this->log_info . ' successfully finished collecting data');
            $this->finish();

            $output->writeln("Successfully exported data for {$this->report_name} {$this->start_date} - {$this->end_date}({$this->brand})");

            return 1;
        }catch (Exception $exception) {
            $this->app['monolog']->addError($this->log_info . "Error: {$exception->getMessage()}");
            $output->writeln("Failed to export data for {$this->report_name} {$this->start_date} - {$this->end_date}({$this->brand})");
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    final protected function init(InputInterface $input, OutputInterface $output): void
    {
        $this->start_date = Carbon::parse($input->getArgument('start_date'))->format('Y-m-d');
        $this->end_date = Carbon::parse($input->getArgument('end_date'))->format('Y-m-d');
        $this->brand = $input->getArgument('brand');
        $this->file_path = $input->getArgument('file_path');
        $this->gaming_site_id = $this->license->getLicSetting('game_site_ids')[$this->brand];
        $this->file_name = $input->getArgument('file_name') ?? $this->report_name . "_" . $this->gaming_site_id . "_" . $this->start_date;
        $this->setLatestTimeZoneChange($this->end_date);
        if (!$input->getOption('without-headers')) {
            $this->setHeaders();
            $this->addHeaders();
        }
    }

    /**
     * @return void
     */
    final protected function finish(): void
    {
        $this->prepareCsvData();
        $this->generateCsvFile();
    }

    /**
     * @return void
     */
    private function generateCsvFile(): void
    {
        if (!file_exists($this->file_path) && !mkdir($this->file_path, 0755, true) && !is_dir($this->file_path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $this->file_path));
            $this->app['monolog']->addError($this->log_info . ' Directory "%s" was not created');
        }

        $f = fopen($this->file_path . "/{$this->file_name}.csv",
            'x+');

        if (!$f) {
            throw new \RuntimeException("Can't create the file");
            $this->app['monolog']->addError($this->log_info . ' Cant create the file');
        }

        foreach ($this->csv as $row) {
            fputcsv($f, $row);
        }
        fclose($f);
    }

    /**
     * @return Builder
     */
    protected function getUsersQuery(): Builder
    {
        return $this->connection->table('users')
            ->select(['users.id'])
            ->join('users_settings', 'users_settings.user_id', '=', 'users.id')
            ->where('users.country', 'CA')
            ->where('users_settings.setting', 'main_province')
            ->where('users_settings.value', 'ON');
    }

    /**
     * @return Builder
     */
    protected function getTestUsersQuery(): Builder
    {
        return $this->connection->table('users_settings')
            ->select(['users_settings.user_id'])
            ->where('users_settings.setting', 'test_account');
    }

    /**
     * @return Builder
     */
    protected function getZeroedUsersGameSessions(): Builder
    {
        return $this->connection->table('users_game_sessions')
            ->select(['users_game_sessions.id'])
            ->where('users_game_sessions.bet_amount', "=", '0')
            ->where('users_game_sessions.win_amount', "=", '0')
            ->where('users_game_sessions.result_amount', "=", '0')
            ->where('users_game_sessions.balance_start', "=", '0')
            ->where('users_game_sessions.bet_cnt', "=", '0')
            ->where('users_game_sessions.bets_rollback', "=", '0')
            ->where('users_game_sessions.wins_rollback', "=", '0')
            ->where('users_game_sessions.win_cnt', "=", '0');
    }

    /**
     * @param string $columnName
     * @return string
     */
    protected function getDateRawQuery(string $columnName): string
    {
        if ($this->isDaylightSavingsTimeEnabled()) {
            return "IF(
                $columnName < '{$this->latest_timezone_change_date->format('Y-m-d H:i:s')}',
                DATE_FORMAT($columnName - interval " . static::INTERVAL_TIME . " hour, '%Y-%m-%d'),
                DATE_FORMAT($columnName - interval " . static::DST_INTERVAL_TIME . "  hour, '%Y-%m-%d')
            )";

        }

        return "IF(
            $columnName < '{$this->latest_timezone_change_date->format('Y-m-d H:i:s')}',
            DATE_FORMAT($columnName - interval " . static::DST_INTERVAL_TIME . "  hour, '%Y-%m-%d'),
            DATE_FORMAT($columnName - interval " . static::INTERVAL_TIME . " hour, '%Y-%m-%d')
        )";
    }


    /**
     * @return string
     */
    protected function getDeviceTypeRawQuery(): string
    {
        return "(CASE WHEN us.equipment = 'pc' THEN 0 WHEN us.equipment = 'macintosh' THEN 0 ELSE 1 END)";
    }

    /**
     * @return string
     */
    protected function getWithdrawableWinsSubQuery(): string
    {
        return "(
                SELECT
                    ct3.id
                FROM
                    cash_transactions AS ct3
                WHERE
                    ct3.user_id = ct.user_id
                    AND ct3.description = ct.description
                    AND ct3.transactiontype IN (34, 52, 54)
                LIMIT 1
            )";
    }

    /**
     * @return string
     */
    protected function getMicroGamesSubQuery(): string
    {
        return
            "(CASE
                WHEN {$this->getDeviceTypeRawQuery()} = 0 THEN (
                SELECT
                    micro_games.id
                FROM
                    micro_games
                WHERE
                    bt.game_id = micro_games.game_id
                    AND micro_games.device_type_num = 0)
                ELSE (
                SELECT
                    mg2.id
                FROM
                    micro_games mg2
                WHERE
                    mg2.id = (
                    SELECT
                        mg3.mobile_id
                    FROM
                        micro_games mg3
                    WHERE
                        bt.game_id = mg3.game_id
                        AND mg3.mobile_id IS NOT NULL
                        AND mg3.mobile_id != 0
                    )
                )
            END)";
    }

    /**
     * @return string
     */
    protected function getUserSessionsBonusEntriesSubQuery(): string
    {
        return "(
            SELECT
                us2.id
            FROM
                users_sessions AS us2
            WHERE
                us2.user_id = be.user_id
                AND us2.created_at < be.last_change
            ORDER BY
                us2.created_at DESC
            LIMIT 1)";
    }

    /**
     * @return string
     */
    protected function getUserSessionsCashTransactionsSubQuery(): string
    {
        return "(
            SELECT
                us2.id
            FROM
                users_sessions AS us2
            WHERE
                us2.user_id = ct.user_id
                AND us2.created_at < ct.timestamp
            ORDER BY
                us2.created_at DESC
            LIMIT 1)";
    }

    protected function getBetsRollbacksDescriptionSubQuery($query)
    {
        return $query->where("ct.description", "LIKE", "%play mode: normal, rollback type: bets%")
            ->orWhere(function ($q) {
                $q->where("ct.description", "LIKE", "%rollback adjustment of%")
                    ->where("ct.description", "LIKE", "%play mode: normal%");
            });
    }

    /**
     * @return void
     */
    abstract protected function prepareCsvData(): void;

    /**
     * @return void
     */
    abstract protected function collectData(): void;

    /**
     * @return void
     */
    abstract protected function setHeaders(): void;

    /**
     * @return void
     */
    protected function addHeaders(): void
    {
        $this->csv[] = $this->headers;
    }

    /**
     * @return void
     */
    protected function collectDataFromShardTables(): void
    {
        $do_master = !count(DB::getNodesList());

        if ($do_master) {
            $this->processNode(DB::getMasterConnection());
            return;
        }

        DB::loopNodes(
            function (Connection $connection) {
                $this->processNode($connection);
            });
    }

    /**
     * @param Connection $connection
     * @return void
     */
    protected function processNode(Connection $connection): void
    {
        $this->connection = $connection;
        $this->connection->setFetchMode(PDO::FETCH_ASSOC);

        $this->collectData();
    }

    /**
     * @param int $year
     * @return Carbon|null
     */
    protected function getSecondSundayInMarch(int $year): ?Carbon
    {
        $date = Carbon::create($year, 3, 1);
        $date->modify('second sunday of march');
        $date->setTime(7, 0, 0);

        return $date;
    }

    /**
     * @param int $year
     * @return Carbon|null
     */
    protected function getFirstSundayInNovember(int $year): ?Carbon
    {
        $date = Carbon::create($year, 11, 1);
        $date->modify('first sunday of november');
        $date->setTime(6, 0, 0);

        return $date;
    }

    /**
     * @param Carbon $date
     * @return Carbon|null
     */
    protected function getLatestTimeZoneChange(Carbon $date): ?Carbon
    {
        $year = $date->year;
        $secondSundayInMarch = $this->getSecondSundayInMarch($year);
        $firstSundayInNovember = $this->getFirstSundayInNovember($year);

        if ($firstSundayInNovember->lt($date)) {
            return $firstSundayInNovember;
        }

        if ($secondSundayInMarch->lt($date)) {
            return $secondSundayInMarch;
        }

        return $this->getFirstSundayInNovember($date->subYear()->year);
    }

    /**
     * @param Carbon $date
     * @return void
     */
    protected function setLatestTimeZoneChange(string $date): void
    {
        $date = Carbon::parse($date);

        $this->latest_timezone_change_date = $this->getLatestTimeZoneChange($date);
    }

    /**
     * @return bool
     */
    protected function isDaylightSavingsTimeEnabled(): bool
    {
        return $this->latest_timezone_change_date->month === 3;
    }
}
