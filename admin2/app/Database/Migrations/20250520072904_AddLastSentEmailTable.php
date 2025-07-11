<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use App\Extensions\Database\FManager as DB;
class AddLastSentEmailTable extends Migration
{
    protected string $table;
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->table = 'mail_last_sent_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();

                $table->create();

                // Columns
                $table->unsignedBigInteger('id')->autoIncrement();
                $table->bigInteger('user_id')->nullable(false);
                $table->string('mail_trigger')->nullable(false);
                $table->dateTime('created_at')->nullable(false);
                $table->dateTime('updated_at')->nullable(false);

                // Indexes
                $table->unique(['user_id', 'mail_trigger']);
            });

            DB::loopNodes(function ($connection) {
                $connection->statement("ALTER TABLE {$this->table} MODIFY id bigint(21) UNSIGNED NOT NULL AUTO_INCREMENT");
                $connection->statement("ALTER TABLE {$this->table} MODIFY user_id bigint(21) UNSIGNED NOT NULL");
                $connection->statement("ALTER TABLE {$this->table} MODIFY created_at timestamp default CURRENT_TIMESTAMP not null");
                $connection->statement("ALTER TABLE {$this->table} MODIFY updated_at timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP not null");
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
