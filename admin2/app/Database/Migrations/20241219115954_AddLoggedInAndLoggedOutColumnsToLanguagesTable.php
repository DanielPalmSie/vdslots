<?php

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddLoggedInAndLoggedOutColumnsToLanguagesTable extends Migration
{
    protected Builder $schema;
    private string $table;

    public function init(): void
    {
        $this->table = 'languages';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();

            $table->tinyInteger('logged_in')
                ->default(1)
                ->nullable(false);

            $table->tinyInteger('logged_out')
                ->default(1)
                ->nullable(false);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->dropColumn('logged_in');
            $table->dropColumn('logged_out');
        });
    }
}
