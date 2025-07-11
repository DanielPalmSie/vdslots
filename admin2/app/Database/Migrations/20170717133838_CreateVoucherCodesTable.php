<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateVoucherCodesTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'voucher_codes';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->bigIncrements('id');
            $table->string('voucher_code', 50);
            $table->string('voucher_name', 50);
            $table->unsignedInteger('bonus_id');
            $table->unsignedInteger('award_id');
            $table->smallInteger('exclusive');
            $table->unsignedInteger('count');
            $table->string('requirements', 1000);
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
