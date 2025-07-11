<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnToCampaignTemplates extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'messaging_campaign_templates';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->boolean('is_newsletter');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('is_newsletter');
        });
    }}
