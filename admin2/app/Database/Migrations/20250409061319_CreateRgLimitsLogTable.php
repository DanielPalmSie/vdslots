<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateRgLimitsLogTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'rg_limits_log';
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
            $table->bigInteger('requester_id');
            $table->enum('requester_type', ['bo_user', 'system', 'user'])->default('user');
            $table->string('limit_type', 25);
            $table->string('limit_span', 10);
            $table->bigInteger('pre_value');
            $table->bigInteger('post_value');
            $table->enum(
                'request_type',
                ['add', 'change', 'remove', 'remove_no_cool_off', 'add_forced_until', 'remove_forced_until']
            );
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('forced_until');
            $table->boolean('is_forced')->default(false);
            $table->boolean('is_remote_request')->default(false);
            $table->index(['user_id', 'limit_type', 'applied_at'], 'user_id_limit_type_applied_at');
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
