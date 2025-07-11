<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreatePromotionContactTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'mails_promo_contact';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        // Check if the table already exists
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->bigIncrements('id');
                $table->bigInteger('mobile');
                $table->string('mail');
                // promotion: tag key
                $table->string('tag');
                $table->string('country');
                $table->text('descr');
                $table->string('extra');
                $table->tinyInteger('important')->default(0);
                $table->tinyInteger('track_events')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        // Drop the table only if it exists
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
    }
}
