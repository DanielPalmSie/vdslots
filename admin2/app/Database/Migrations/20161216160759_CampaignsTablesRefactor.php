<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class CampaignsTablesRefactor extends Migration
{
    protected $campaigns_templates;
    protected $campaigns;
    protected $old_campaigns;

    protected $schema;

    public function init()
    {
        $this->campaigns_templates = 'messaging_campaign_templates';
        $this->campaigns = 'messaging_campaigns';
        $this->old_campaigns = 'messaging_campaigns';
        $this->schema = $this->get('schema');
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->drop($this->old_campaigns);

        $this->schema->create($this->campaigns_templates, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedTinyInteger('template_type');
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('named_search_id');
            $table->unsignedBigInteger('bonus_template_id')->nullable();
            $table->unsignedBigInteger('voucher_template_id')->nullable();
            $table->string('recurring_type', 10)->nullable();
            $table->time('start_time')->nullable();
            $table->date('start_date')->nullable();
            $table->string('recurring_days')->nullable();
            $table->timestamp('recurring_end_date')->nullable();
            $table->nullableTimestamps();
        });

        $this->schema->create($this->campaigns, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedTinyInteger('type');
            $table->unsignedBigInteger('campaign_template_id');
            $table->unsignedBigInteger('bonus_id')->nullable();
            $table->string('voucher_name')->nullable();
            $table->timestamp('sent_time')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedBigInteger('contacts_count')->default(0);
            $table->unsignedBigInteger('sent_count')->default(0);
            $table->text('stats');
            $table->nullableTimestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->campaigns_templates);

        $this->schema->drop($this->campaigns);

        $this->schema->create($this->old_campaigns, function (Blueprint $table) {
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
}
