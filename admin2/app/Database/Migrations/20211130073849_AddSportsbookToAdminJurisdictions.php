<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddSportsbookToAdminJurisdictions extends Migration
{
    private Connection $connection;
    private string $table;
    private string $config_name;

    public function init()
    {
        $this->table = 'config';
        $this->config_name = 'admin2.jurisdiction';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (getenv('APP_SHORT_NAME') === 'MV') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->insert([
                'config_name' => $this->config_name,
                'config_value' => json_encode($this->getConfigValue(), JSON_THROW_ON_ERROR),
                'config_tag' => '',
                'config_type' => json_encode(['Type' => 'text'], JSON_THROW_ON_ERROR),
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') === 'MV') {
            return;
        }

        $this->removeConfig();
    }

    private function removeConfig(): void
    {
        $this->connection
            ->table($this->table)
            ->where('config_name', $this->config_name)
            ->delete();
    }

    private function getConfigValue(): array
    {
        return [
            'all' => '',
            'gb' => "AND country = 'GB'",
            'mt' => "AND country = 'MT'",
            'se' => "AND country = 'SE'",
            'dk' => "AND country = 'DK'",
            'it' => "AND country = 'IT'",
            'mga' => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES')",
            'mga sportsbook' => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES')",
            'mt sportsbook' => "AND country = 'MT'",
            'se sportsbook' => "AND country = 'SE'",
        ];
    }
}
