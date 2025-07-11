<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class CreateResponsibilityCheckTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'responsibility_check';
        $this->schema = $this->get('schema');
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('fullname', 255);
            $table->string('country', 5);
            $table->string('brand', 20);
            $table->timestamp('requested_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('status', 50);
            $table->string('type', 20);
            $table->enum('solution_provider', ['GBG', 'BeBettor']);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
