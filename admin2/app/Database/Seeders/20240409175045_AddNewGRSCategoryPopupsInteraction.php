<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddNewGRSCategoryPopupsInteraction extends Seeder
{
    private string $table;
    private string $category_slug;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->category_slug = 'popups_interaction';
        $this->jurisdictions = phive('Licensed')->getSetting('country_by_jurisdiction_map') ?? [];
    }

    public function up()
    {
        $insert = [];
        foreach ($this->jurisdictions as $jurisdiction) {
            // parent category
            $insert[] = [
                'name' => $this->category_slug,
                'jurisdiction' => $jurisdiction,
                'title' => 'Popups Interaction last _DAYS days',
                'score' => 0,
                'type' => 'interval',
                'category' => '',
                'section' => 'RG',
                'data' => json_encode(["replacers" => ["_DAYS" => "30"]], JSON_THROW_ON_ERROR),
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
                ->whereIn('jurisdiction', $this->jurisdictions)
                ->where(function ($query) {
                    $query->where('category', $this->category_slug)->orWhere('name', $this->category_slug);
                })
                ->delete();
        }, true);
    }
}