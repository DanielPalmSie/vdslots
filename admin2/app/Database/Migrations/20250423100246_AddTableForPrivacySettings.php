<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use App\Extensions\Database\FManager as DB;

class AddTableForPrivacySettings extends Migration
{
    protected string $table;
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->table = 'users_privacy_settings';
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
                $table->enum('channel', ['email', 'sms', 'app', 'direct_mail', 'voice', 'calls'])->nullable(false);
                $table->enum('type', ['new', 'promotions', 'updates', 'offers'])->nullable(false);
                $table->enum('product', ['casino', 'sports', 'bingo', 'poker'])->nullable(true);
                $table->boolean('opt_in')->default(false);
                $table->dateTime('updated_at');

                // Indexes
                $table->unique(['user_id', 'channel', 'type', 'product']);
            });

            DB::loopNodes(function ($connection) {
                $connection->statement("ALTER TABLE {$this->table} MODIFY id bigint(21) UNSIGNED NOT NULL AUTO_INCREMENT");
                $connection->statement("ALTER TABLE {$this->table} MODIFY user_id bigint(21) UNSIGNED NOT NULL");
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
