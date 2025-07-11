<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddIbanString extends Migration
{
    protected $table;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    private $strings = [
        [
            'alias' => 'iban',
            'language' => 'en',
            'value' => 'IBAN',
        ],
        [
            'alias' => 'bluem.iban.check.failed',
            'language' => 'en',
            'value' => 'IBAN check failed',
        ],
    ];

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->strings as $string) {
            $old_string = $this->connection
                ->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->first();

            if (empty($old_string)) {
                $this->connection->table($this->table)->insert($string);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->strings as $string) {
            $this->connection
                ->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->delete();
        }
    }
}
