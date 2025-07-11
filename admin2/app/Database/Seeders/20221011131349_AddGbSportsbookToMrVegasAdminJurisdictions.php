<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddGbSportsbookToMrVegasAdminJurisdictions extends Seeder
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

    public function up()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where('config_name', '=', $this->config_name)
            ->update(['config_value' => json_encode($this->getConfigValue(), JSON_THROW_ON_ERROR)
            ]);
    }

    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where('config_name', '=', $this->config_name)
            ->update(['config_value' => json_encode($this->getPrevConfigValue(), JSON_THROW_ON_ERROR)
            ]);
    }

    /**
     * Get previous config values without GB
     *
     * @return array
     */
    private function getPrevConfigValue(): array
    {
        return [
            'all' => '',
            'mt' => "AND country = 'MT'",
            'mga' => "AND country NOT IN ('GB', 'SE', 'DK')",
            'se' => "AND country = 'SE'",
            'dk' => "AND country = 'DK'",
            'gb' => "AND country = 'GB'",
            'mga sportsbook' => "AND country NOT IN ('GB', 'SE', 'DK')",
            'mt sportsbook' => "AND country = 'MT'",
            'se sportsbook' => "AND country = 'SE'"
        ];
    }

    private function getConfigValue(): array
    {
        return [
            'all' => '',
            'mt' => "AND country = 'MT'",
            'mga' => "AND country NOT IN ('GB', 'SE', 'DK')",
            'se' => "AND country = 'SE'",
            'dk' => "AND country = 'DK'",
            'gb' => "AND country = 'GB'",
            'mga sportsbook' => "AND country NOT IN ('GB', 'SE', 'DK')",
            'mt sportsbook' => "AND country = 'MT'",
            'gb sportsbook' => "AND country = 'GB'"
        ];
    }
}