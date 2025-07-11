<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddErrorsInternalExternalValidation extends Migration
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private array $to_insert = [
        [
            'language' => 'en',
            'alias' => 'errors.user.external_validation',
            'value' => "External service didn't recognize user with such data",
        ],
        [
            'language' => 'en',
            'alias' => 'errors.user.internal_validation',
            'value' => "User's data is not correct",
        ],
        [
            'language' => 'en',
            'alias' => 'errors.user.not_valid_iban',
            'value' => "The IBAN number that you've entered is not valid",
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)
            ->insert($this->to_insert);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->to_insert as $data) {
            $this->connection
                ->table($this->table)
                ->where('alias', $data['alias'])
                ->where('language', $data['language'])
                ->delete();
        }
    }
}
