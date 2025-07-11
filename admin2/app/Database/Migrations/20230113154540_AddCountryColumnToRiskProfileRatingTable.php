<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Models\RiskProfileRating;

class AddCountryColumnToRiskProfileRatingTable extends Migration
{
    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
        $this->masterConnection = DB::getMasterConnection();
        $this->isShardedDB = $this->getContainer()['capsule.vs.db']['sharding_status'];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->isShardedDB) {
            DB::loopNodes(function ($connection) {
                $sm = $connection->getDoctrineSchemaManager();
                $table = $sm->listTableDetails($this->table);
                // Note: not all shards has PK. This why we need handle it manually
                if ($table->hasPrimaryKey()) {
                    throw new Exception("The database '{$connection->getDatabaseName()}' has Primary Key.
                Please drop the PK manually before start the migration.
                Otherwise it will corrupt migration process.");
                }

            }, true);
        } else {
            $sm = $this->masterConnection->getDoctrineSchemaManager();
            $table = $sm->listTableDetails($this->table);

            if ($table->hasPrimaryKey()) {
                $this->schema->table($this->table, function (Blueprint $table) {
                    $table->dropPrimary(['name', 'category', 'section']);
                });
            }
        }

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('jurisdiction', 10)->after('name')->nullable();
            $table->dropUnique(['name', 'category', 'section']);
            $table->unique(['name', 'jurisdiction', 'category', 'section']);
        });

        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        // set default jurisdiction for exists settings

        if ($this->isShardedDB) {
            DB::loopNodes(function ($connection) use ($country_jurisdiction_map) {
                $connection->table($this->table)
                    ->update(['jurisdiction' => $country_jurisdiction_map['default']]);

            }, true);
        } else {
            $this->masterConnection->table($this->table)
                ->update(['jurisdiction' => $country_jurisdiction_map['default']]);
        }

        // duplicate settings for all jurisdiction except default
        $rows = RiskProfileRating::all();
        foreach ($country_jurisdiction_map as $country => $jurisdiction) {
            if ($country == 'default') {
                continue;
            }
            $rows->map(function ($row) use ($jurisdiction) {
                $row->jurisdiction = $jurisdiction;
                return $row;
            })->tap(function ($data) {
                $insert = array_map(function ($item) {
                    if (!empty($item['data'])) {
                        $item['data'] = json_encode($item['data']);
                    }
                    return $item;
                }, $data->toArray());

                /** @var \Illuminate\Support\Collection $data */
                DB::bulkInsert($this->table, null, $insert, $this->masterConnection);

                if ($this->isShardedDB) {
                    DB::bulkInsert($this->table, null, $insert);
                }
                return $data;
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        // remove duplicated settings for non default jurisdictions
        unset($country_jurisdiction_map['default']);

        if ($this->isShardedDB) {
            DB::loopNodes(function ($connection) use ($country_jurisdiction_map) {
                $connection->table($this->table)
                    ->whereIn('jurisdiction', $country_jurisdiction_map)
                    ->delete();

            }, true);
        } else {
            $this->masterConnection->table($this->table)
                ->whereIn('jurisdiction', $country_jurisdiction_map)
                ->delete();
        }

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropUnique(['name', 'jurisdiction', 'category', 'section']);
            $table->dropColumn('jurisdiction');
            $table->unique(['name', 'category', 'section']);
        });
    }
}
