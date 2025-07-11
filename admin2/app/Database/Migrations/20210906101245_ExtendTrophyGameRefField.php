<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class ExtendTrophyGameRefField extends Migration
{
    /**
     * Do the migration
     */
    protected $table;
    protected const COLUMN = "game_ref";

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'trophies';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        // Check if the column exists before making changes. Changes cannot be made to a column that isn't there.
        if ($this->schema->hasColumn($this->table, self::COLUMN)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();

                // Increase the size from 50 to 100 to better accommodate the websites requirements.
                $table->string(self::COLUMN, 100)->change();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        // Check if the column exists before making changes. Changes cannot be made to a column that isn't there.
        if ($this->schema->hasColumn($this->table, self::COLUMN)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();

                // Revert the size back to 50 as was the case before.
                $table->string(self::COLUMN, 50)->change();
            });
        }
    }
}
