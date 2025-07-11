<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Schema\Blueprint;

class AddBrandIdOnDbetBonusTypeTable extends Migration
{
    protected $tableBonusTypes;
    protected $schema;
    private $brand;
    private $brandId;
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->tableBonusTypes = 'bonus_types';
        $this->schema = $this->get('schema');
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->brandId = phive('Distributed')->getLocalBrandId();
    }


    /*
     |--------------------------------------------------------------------------
     | Update bonus_types table adding brand_id
     |--------------------------------------------------------------------------
     */
    public function up()
    {
        $this->init();

        if ($this->brand !== phive('BrandedConfig')::BRAND_DBET) {
            return;
        }

        if ($this->schema->hasColumn($this->tableBonusTypes, 'brand_id')) {
            // Handle the master adjustment
            $this->checkAndUpdateBrandIdValue($this->connection, $this->tableBonusTypes, $this->brandId);

            // Handle the master and shards
            $dataCopy = $this->brandId;
            $this->schema->table($this->tableBonusTypes, function (Blueprint $tableBonusTypes) use ($dataCopy) {
                $tableBonusTypes->asSharded();
                DB::loopNodes(function (Connection $shardConnection) use ($dataCopy) {
                    $this->checkAndUpdateBrandIdValue($shardConnection, $this->tableBonusTypes, $dataCopy);
                }, false);
            });

        } else {
            // Handle the master and shards adjustment
            $this->schema->table($this->tableBonusTypes, function (Blueprint $tableBonusTypes) {
                $tableBonusTypes->unsignedTinyInteger('brand_id')->after('award_id')->default($this->brandId);
            });
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Update bonus_types table removing brand_id
    |--------------------------------------------------------------------------
    */
    public function down()
    {
        $this->init();

        if ($this->brand !== phive('BrandedConfig')::BRAND_DBET) {
            return;
        }

        if ($this->schema->hasColumn($this->tableBonusTypes, 'brand_id')) {
            $this->schema->table($this->tableBonusTypes, function (Blueprint $tableBonusTypes) {
                $tableBonusTypes->dropColumn('brand_id');
            });
        }
    }

    private function checkAndUpdateBrandIdValue($connection, $tableBonusTypes, $data)
    {
        if ($this->schema->hasColumn($tableBonusTypes, 'brand_id')) {

            $exists = $connection
                ->table($tableBonusTypes)
                ->where('brand_id', '!=', $data)
                ->exists();

            if ($exists) {
                $connection
                    ->table($tableBonusTypes)
                    ->where('brand_id', '!=', $data)
                    ->update([
                        'brand_id' => $data,
                    ]);
            }
        }
    }

}
