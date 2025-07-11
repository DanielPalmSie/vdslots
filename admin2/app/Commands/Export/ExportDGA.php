<?php

namespace App\Commands\Export;

use App\Extensions\Database\Connection\Connection;
use Illuminate\Support\Collection;
use PDO;

class ExportDGA extends ExportICS
{
    protected string $jurisdiction = 'dga';

    protected function configure()
    {
        parent::configure();
        $this->setName('export:dga')
            ->setDescription('Export data for DGA reports testing');
    }

    protected function collectDataFromMasterTables(): void
    {
        $this->putDataIntoFile('game_country_versions', $this->getGamesCountryVersions());
        parent::collectDataFromMasterTables();
    }

    protected function getGamesCountryVersions(): Collection
    {
        return $this->getMasterConnection()->table('game_country_versions')->where('country', 'DK')->get();
    }

    protected function processNode(Connection $connection): void
    {
        $this->connection = $connection;
        $this->connection->setFetchMode(PDO::FETCH_ASSOC);

        $this->setShardCurrent($this->connection->getConfig('replica_node_id'));

        $this->createUsersTableTemp();

        $users = $this->getUsers();

        if ($users->isEmpty()) {
            $this->dropUsersTempTable();
            return;
        }

        $this->putDataIntoFile('users', $users);
        $users = null; // cleaned up

        $this->putDataIntoFile('users_game_sessions', $this->getUsersGameSessions());

        $this->putDataIntoFile('users_sessions', $this->getUsersSessions());

        $this->putDataIntoFile('users_settings', $this->getUsersSettings());

        $this->putDataIntoFile('bets', $this->getBets());

        $this->putDataIntoFile('wins', $this->getWins());

        $this->dropUsersTempTable();
    }
}
