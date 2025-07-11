<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForPoolxHistoryDbet extends SeederTranslation
{
    protected Connection $connection;
    protected array $data;
    protected string $table  = 'localized_strings';

    public function init()
    {
        parent::init();
        $this->data = $this->getTranslationSeederData();
        $this->connection = DB::getMasterConnection();
    }

    protected function getTranslationSeederData(): array
    {
        if (getenv('APP_SHORT_NAME') !== 'DBET') {
            return [];
        }

        $translations = [
            'sports-betting-history-poolx' => 'Super History',
            'supertipset-history.my.all.time' => 'My All Time Super',
        ];
        $languages = ['en', 'da', 'de', 'es', 'fi', 'hi', 'it', 'ja', 'no', 'pt', 'sv'];

        return array_fill_keys($languages, $translations);
    }
}