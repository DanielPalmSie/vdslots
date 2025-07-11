<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateUsersRatingTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'risk_profile_rating_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('user_id');
                $table->string('rating_type', 8);
                $table->integer('rating');
                $table->timestamp('created_at')->useCurrent();
                $table->index('user_id');
                $table->index(['user_id', 'created_at']);
                $table->index(['user_id', 'created_at', 'rating_type']);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
    }
}
