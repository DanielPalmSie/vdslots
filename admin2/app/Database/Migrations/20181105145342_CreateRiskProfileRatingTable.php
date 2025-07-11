<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateRiskProfileRatingTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->string('name');
            $table->string('title'); // human readable name
            $table->string('type'); // option, interval, multiplier
            $table->integer('score')->default(0);
            $table->string('category'); // parent: null, child: name of the parent row
            $table->string('section', 50); // value in [AML, RG]
            $table->text('data');

        });
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->primary(['name', 'category', 'section']);
            $table->unique(['name', 'category', 'section']);
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
