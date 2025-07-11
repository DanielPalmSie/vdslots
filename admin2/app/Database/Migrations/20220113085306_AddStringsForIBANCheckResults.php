<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddStringsForIBANCheckResults extends Migration
{
    private $table = 'localized_strings';
    protected $connection;

    private $new_strings = [
        [
            'alias' => 'bluem.iban.check.not_enabled',
            'language' => 'en',
            'value' => 'IBAN check not enabled'
        ],
        [
            'alias' => 'bluem.iban.check.connection_error',
            'language' => 'en',
            'value' => 'IBAN check failed with connection error'
        ],
        [
            'alias' => 'bluem.iban.check.missing_result',
            'language' => 'en',
            'value' => 'IBAN check failed with missing result'
        ],
        [
            'alias' => 'bluem.iban.check.service_down',
            'language' => 'en',
            'value' => 'IBAN check failed as check service is down'
        ],
    ];

    function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach($this->new_strings as $string) {
            $localized_string = $this->connection
                ->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->first();

            if(!empty($localized_string)) {
                continue;
            }

            $this->connection->table($this->table)->insert($string);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach($this->new_strings as $string) {
            $this->connection
                ->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->delete();
        }
    }
}
