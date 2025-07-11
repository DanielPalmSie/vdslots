<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddexternalselfexclusionintheRGGRS extends Seeder
{
    public function init()
    {
        $this->table = 'risk_profile_rating';
    }

    public function up()
    {
        $insert = [];
        $jurisdictions = [
            'DGA',
            'SGA',
            'UKGC'
        ];

        foreach ($jurisdictions as $jurisdiction) {
                $insert[] = [
                    'title' => "Account has been externally self-excluded",
                    'name' => "externally-excluded",
                    'jurisdiction' => $jurisdiction,
                    'score' => 0,
                    'type' => '',
                    'category' => "self_locked_excluded",
                    'section' => 'RG',
                    'data' => '',
                ];
        }
        DB::loopNodes(function ($connection) use ($insert) {
            $connection->table($this->table)
                ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section'], ['score']);
        }, true);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->whereIn('category', ['self_locked_excluded'])
                ->whereIn('name', ['externally-excluded'])
                ->delete();
        }, true);
    }
}
