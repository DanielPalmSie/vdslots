<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class SpainPeriodicalWarningTranslations extends Migration
{
    private string $table = 'localized_strings';

    private array $items = [
        [
            'alias' => 'time.elapsed',
            'language' => 'en',
            'value' => 'Time elapsed'
        ],
        [
            'alias' => 'spend.spent',
            'language' => 'en',
            'value' => 'Amount spent',
        ]
    ];

    /**
     * Do the migration
     */
    public function up()
    {
        foreach($this->items as $item) {
            $exists = DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            DB::getMasterConnection()
                ->table($this->table)
                ->insert([$item]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach($this->items as $item) {
            DB::getMasterConnection()
                ->table($this->table)
                ->whereIn('alias', $item['alias'])
                ->whereIn('language', $item['language'])
                ->delete();
        }
    }
}
