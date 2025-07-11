<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateOfflineCampaignsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'offline_campaigns';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type');
            $table->bigInteger('template_id');
            $table->bigInteger('named_search');
            $table->timestamps();
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
