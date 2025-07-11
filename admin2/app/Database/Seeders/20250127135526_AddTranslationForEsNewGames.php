<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddTranslationForEsNewGames extends Seeder
{
    protected array $data = [];
    private string $table = 'localized_strings';
    private string $newGamesTag = 'new.cgames';
    private string $esNewGamesTag = 'esnew.cgames';
    private $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $newGamesTranslations = $this->connection
            ->table($this->table)
            ->where('alias', $this->newGamesTag)
            ->get()
            ->toArray();

        $esNewGamesTranslations = array_map(function($newGameTranslation) {
            return [
                'alias' => $this->esNewGamesTag,
                'language' => $newGameTranslation->language,
                'value' => $newGameTranslation->value
            ];
        }, $newGamesTranslations);

        $this->connection
            ->table($this->table)
            ->insert($esNewGamesTranslations);
    }

    /**
     * Undo the seeder
     */
    public function down()
    {

        $this->connection
            ->table($this->table)
            ->where('alias', $this->esNewGamesTag)
            ->delete();
    }
}
