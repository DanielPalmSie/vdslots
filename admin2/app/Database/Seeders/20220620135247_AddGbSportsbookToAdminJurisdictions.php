<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddGbSportsbookToAdminJurisdictions extends Seeder
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
        if (getenv('APP_SHORT_NAME') === 'MV') {
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
        if (getenv('APP_SHORT_NAME') === 'MV') {
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
            'gb' => "AND country = 'GB'",
            'mt' => "AND country = 'MT'",
            'se' => "AND country = 'SE'",
            'dk' => "AND country = 'DK'",
            'it' => "AND country = 'IT'",
            'mga' => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES')",
            'mga sportsbook' => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES')",
            'mt sportsbook' => "AND country = 'MT'",
            'se sportsbook' => "AND country = 'SE'"
        ];
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
            'gb sportsbook' => "AND country = 'GB'"
        ];
    }
}