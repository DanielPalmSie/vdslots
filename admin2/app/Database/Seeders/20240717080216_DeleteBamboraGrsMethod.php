<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class DeleteBamboraGrsMethod extends Seeder
{
    private string $table;
    private string $category_slug;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->category_slug = 'deposit_method';
    }

    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('category', $this->category_slug)
                ->where('name', 'bambora')
                ->delete();
        }, true);
    }

    public function down()
    {
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');

        foreach ($country_jurisdiction_map as $jurisdiction) {
            $insert = [
                'title' => 'Bambora',
                'name' => 'bambora',
                'jurisdiction' => $jurisdiction,
                'score' => 0,
                'type' => '',
                'category' => $this->category_slug,
                'section' => 'AML',
                'data' => '',
            ];
            DB::loopNodes(function ($connection) use ($insert) {
                $connection->table($this->table)
                    ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section'], ['score']);

            }, true);
        }
    }
}