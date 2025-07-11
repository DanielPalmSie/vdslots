<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class ChangeBetType extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'sport_transactions';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasColumn($this->table, 'bet_type')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('bet_type');
            });
        }
        
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->enum('bet_type', ['bet', 'win']);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
