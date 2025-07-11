<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class SourceOfIncomeErrorTranslation extends Migration
{
    /** @var string */
    private $localized_strings_table;

    /** @var array */
    private $localized_strings_table_items;

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->localized_strings_table = 'localized_strings';
        $this->connection = DB::getMasterConnection();

        $this->localized_strings_table_items = [
            [
                'alias' => 'error.income.type.not.selected',
                'language' => 'en',
                'value' => 'Please select source of income'
            ],
        ];
    }
    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->localized_strings_table_items as $item) {
            $exists = $this->connection
                ->table($this->localized_strings_table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            $this->connection
                ->table($this->localized_strings_table)
                ->insert([$item]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->localized_strings_table_items as $item) {
            $this->connection
                ->table($this->localized_strings_table)
                ->where('alias', '=', $item['alias'])
                ->where('language', '=', $item['language'])
                ->delete();
        }
    }
}
