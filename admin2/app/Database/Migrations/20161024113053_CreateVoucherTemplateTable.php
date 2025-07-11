<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVoucherTemplateTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'voucher_templates';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('voucher_name', 50);
            $table->string('voucher_code', 50);
            $table->unsignedInteger('count');
            $table->unsignedInteger('bonus_type_template_id')->comment('refers to bonus_type_templates.id');
            $table->unsignedInteger('trophy_award_id')->comment('refers trophy_awards.id');
            $table->smallInteger('exclusive');
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
