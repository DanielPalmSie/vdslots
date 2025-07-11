<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddVoucherTemplateNameColumn extends Migration
{
    protected $voucher_temp_table;
    protected $bonus_temp_table;

    protected $schema;

    public function init()
    {
        $this->voucher_temp_table = 'voucher_templates';
        $this->bonus_temp_table = 'bonus_type_templates';
        $this->schema = $this->get('schema');
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->voucher_temp_table, function (Blueprint $table) {
            $table->string('template_name')->nullable()->after('id');
        });

        $this->schema->table($this->bonus_temp_table, function (Blueprint $table) {
            $table->dropColumn('template_name');
        });

        $this->schema->table($this->bonus_temp_table, function (Blueprint $table) {
            $table->string('template_name')->nullable()->after('id');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->voucher_temp_table, function (Blueprint $table) {
            $table->dropColumn('template_name');
        });
    }
}
