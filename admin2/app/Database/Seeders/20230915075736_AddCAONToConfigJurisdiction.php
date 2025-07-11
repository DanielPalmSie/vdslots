<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddCAONToConfigJurisdiction extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $configName;

    public function init()
    {
        $this->table = 'config';
        $this->configName = 'admin2.jurisdiction';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('config_name', '=', $this->configName)
            ->update(['config_value' => json_encode($this->getNewConfigValues(), JSON_THROW_ON_ERROR)]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('config_name', '=', $this->configName)
            ->update(['config_value' => json_encode($this->getPreviousConfigValues(), JSON_THROW_ON_ERROR)]);
    }

    private function getPreviousConfigValues()
    {
        $prevVSValues = [
            "all" => "",
            "gb" => "AND country = 'GB'",
            "mt" => "AND country = 'MT'",
            "se" => "AND country = 'SE'",
            "dk" => "AND country = 'DK'",
            "it" => "AND country = 'IT'",
            "mga" => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES')",
            "mga sportsbook" => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES')",
            "mt sportsbook" => "AND country = 'MT'",
            "se sportsbook" => "AND country = 'SE'",
            "gb sportsbook" => "AND country = 'GB'"
        ];

        $prevMRVValues = [
            "all" => "",
            "mt" => "AND country = 'MT'",
            "mga" => "AND country NOT IN ('GB', 'SE', 'DK')",
            "se" => "AND country = 'SE'",
            "dk" => "AND country = 'DK'",
            "gb" => "AND country = 'GB'",
            "mga sportsbook" => "AND country NOT IN ('GB', 'SE', 'DK')",
            "mt sportsbook" => "AND country = 'MT'",
            "gb sportsbook" => "AND country = 'GB'"
        ];

        return (getenv('APP_SHORT_NAME') === 'MV') ? $prevMRVValues : $prevVSValues;
    }

    private function getNewConfigValues()
    {
        $newVSValues = [
            "all" => "",
            "gb" => "AND country = 'GB'",
            "mt" => "AND country = 'MT'",
            "se" => "AND country = 'SE'",
            "dk" => "AND country = 'DK'",
            "it" => "AND country = 'IT'",
            "ca-on" => "AND country = 'CA' AND province = 'ON'",
            "mga" => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES', 'CA') OR (country = 'CA' AND province != 'ON')",
            "mga sportsbook" => "AND country NOT IN ('GB','SE', 'DK', 'IT', 'ES')",
            "mt sportsbook" => "AND country = 'MT'",
            "se sportsbook" => "AND country = 'SE'",
            "gb sportsbook" => "AND country = 'GB'"
        ];

        $newMRVValues = [
            "all" => "",
            "mt" => "AND country = 'MT'",
            "mga" => "AND country NOT IN ('GB', 'SE', 'DK', 'CA') OR (country = 'CA' AND province != 'ON')",
            "se" => "AND country = 'SE'",
            "dk" => "AND country = 'DK'",
            "gb" => "AND country = 'GB'",
            "ca-on" => "AND country = 'CA' and province = 'ON'",
            "mga sportsbook" => "AND country NOT IN ('GB', 'SE', 'DK')",
            "mt sportsbook" => "AND country = 'MT'",
            "gb sportsbook" => "AND country = 'GB'"
        ];

        return (getenv('APP_SHORT_NAME') === 'MV') ? $newMRVValues : $newVSValues;
    }
}
