<?php
declare(strict_types=1);

namespace App\Commands\Export;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\ReplicaFManager as DB;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use JsonException;
use PDO;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Videoslots\HistoryMessages\UserSnapshotHistoryMessage;


class UserExport extends ExportBaseCommand
{

    protected $user_id;
    protected ?Connection $connection = null;
    protected array $cmd_options;

    /**
     * @throws JsonException
     */
    protected function configure()
    {
        $this->setName("export:user")
            ->setDescription('Export data for user')
            ->addArgument('user_id', InputArgument::REQUIRED, "Choose id of the user");
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function init(InputInterface $input, OutputInterface $output): void
    {
        $this->user_id = $input->getArgument('user_id');
        parent::init($input, $output);
    }

    /**
     * @return void
     */
    protected function collectData(): void
    {
        $this->processNode(DB::shTable($this->getUserId(), 'users')->getConnection());
    }

    /**
     * @param Connection $connection
     */
    protected function processNode(Connection $connection): void
    {
        $this->connection = $connection;
        $this->connection->setFetchMode(PDO::FETCH_ASSOC);

        /** @uses \Licensed::addRecordToHistory() */
        lic('addRecordToHistory',
            [
              'user_snapshot',
                new UserSnapshotHistoryMessage([
                  'user_id' => (int)$this->getUserId(),
                  'user'=>$this->getUsers()->first(),
                  'users_settings' => $this->getSettings()->toArray(),
                  'rg_limits' => $this->getRgLimits()->toArray(),
                  'event_timestamp' => Carbon::now()->timestamp
                ])
            ], $this->getUserId());

    }

    protected function getUserId()
    {
        return $this->user_id;
    }

    protected function getSettings(): Collection
    {
        return $this->getSettingsQuery()->get();
    }

    protected function getSettingsQuery(): Builder
    {
        return $this->connection->table('users_settings')
            ->where('user_id', '=', $this->getUserId());
    }

    protected function getRgLimits(): Collection
    {
        return $this->getRgLimitsQuery()->get();
    }

    protected function getRgLimitsQuery(): Builder
    {
        return $this->connection->table('rg_limits')
            ->where('user_id', '=', $this->getUserId());
    }

    protected function getUsers(): Collection
    {
        return $this->getUsersQuery()->get();
    }

    protected function getUsersQuery(): Builder
    {
        $user_fields = [
            'id',
            'email',
            'mobile',
            'country',
            'last_login',
            'newsletter',
            'sex',
            'lastname',
            'firstname',
            'address',
            'city',
            'zipcode',
            'dob',
            'preferred_lang',
            'username',
            'register_date',
            'cash_balance',
            'bust_treshold',
            'reg_ip',
            'active',
            'verified_phone',
            'alias',
            'last_logout',
            'cur_ip',
            'logged_in',
            'currency',
            'affe_id',
            'nid'
        ];

        return $this->connection->table('users')
            ->select($user_fields)
            ->where('id', '=', $this->getUserId());
    }
}