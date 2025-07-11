<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateUsersComplaintsResponsesTables extends Migration
{
    protected string $table_complaints = 'users_complaints';
    protected string $table_responses = 'users_complaints_responses';
    protected MysqlBuilder $schema;

    /**
     * Do the migration
     */
    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table_complaints)) {
            $this->schema->create($this->table_complaints, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->index();
                $table->string('ticket_id', 50);
                $table->bigInteger('actor_id');
                $table->string('ticket_url', 2083);
                $table->tinyInteger('type', false, true);
                $table->tinyInteger('status', false, true)
                    ->comment('Status 0 - complaint is deactivated. Status 1 - complaint is active')
                    ->default(0)
                    ->index();

                $table->timestamps();
            });
        }

        if (!$this->schema->hasTable($this->table_responses)) {
            $this->schema->create($this->table_responses, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->index();
                $table->bigInteger('complaint_id')->index();
                $table->bigInteger('actor_id');
                $table->tinyInteger('type', false, true);
                $table->text('description');

                $table->timestamps();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table_responses)) {
            $this->schema->drop($this->table_responses);
        }

        if ($this->schema->hasTable($this->table_complaints)) {
            $this->schema->drop($this->table_complaints);
        }
    }
}
