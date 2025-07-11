<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateRewardIdForXPTrophies extends Seeder
{

    private \App\Extensions\Database\Connection\Connection $connection;
    private $brand;
    private string $tableTrophies;
    private int $awardId;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig');
        $this->tableTrophies = 'trophies';
        $this->awardId = 18146;
    }

    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | Update trophies record
        |--------------------------------------------------------------------------
       */

        $this->init();

        if ($this->brand->getBrand() !== $this->brand::BRAND_MEGARICHES) {
            return;
        }

        $this->connection
            ->table($this->tableTrophies)
            ->where('type', 'xp')
            ->update(['award_id' => $this->awardId]);
    }

    public function down()
    {
        /*
        |--------------------------------------------------------------------------
        | Update trophies record
        |--------------------------------------------------------------------------
       */

        $this->init();

        if ($this->brand->getBrand() !== $this->brand::BRAND_MEGARICHES) {
            return;
        }

        $this->connection
            ->table($this->tableTrophies)
            ->where('type', 'xp')
            ->update(['award_id' => 0]);
    }
}
