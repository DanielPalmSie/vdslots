<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class SMSTemplateScheduleTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'sms_template_schedules';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sms_template_id');
            $table->string('type')->comment('voucher or bonus');
            $table->bigInteger('voucher_template_id')->nullable();
            $table->bigInteger('bonus_template_id')->nullable();
            $table->date('send_date');
            $table->bigInteger('named_search_id');
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
