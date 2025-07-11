<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class RemoveSMSSchedulesAddCampaigns extends Migration
{
    protected $schedule_table;
    protected $campaigns_table;
    protected $schema;

    public function init()
    {
        $this->schedule_table = 'sms_template_schedules';
        $this->campaigns_table = 'messaging_campaigns';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->drop($this->schedule_table);
        $this->schema->create($this->campaigns_table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedTinyInteger('template_type');
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('named_search_id');
            $table->unsignedBigInteger('voucher_template_id')->nullable();
            $table->unsignedBigInteger('bonus_template_id')->nullable();
            $table->timestamp('scheduled_time');
            $table->timestamp('sent_time');
            $table->unsignedTinyInteger('status');
            $table->unsignedBigInteger('result')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->campaigns_table);
        $this->schema->create($this->schedule_table, function (Blueprint $table) {
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
}
