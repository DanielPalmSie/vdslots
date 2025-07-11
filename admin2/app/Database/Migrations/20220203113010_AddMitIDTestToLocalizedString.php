<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddMitIDTestToLocalizedString extends Migration
{
    /** @var string */
    protected $table;

    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $exist = $this->connection
            ->table($this->table)
            ->where('alias', '=', 'verification.method2.dk')
            ->where('language', '=', 'en')
            ->first();

        if (!empty($exist)) {
            return;
        }

        $this->connection
            ->table($this->table)->insert([
                'alias' => 'verification.method2.dk',
                'language' => 'en',
                'value' => 'MitID'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'verification.method2.dk')
            ->where('language', '=', 'en')
            ->where('value', '=', 'MitID')
            ->delete();
    }

}

