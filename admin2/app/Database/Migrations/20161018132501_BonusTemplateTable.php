<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class BonusTemplateTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'bonus_type_templates';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('expire_time', 255);
            $table->integer('num_days')->unsigned()->nullable();
            $table->integer('cost')->unsigned()->default(0);
            $table->integer('reward')->unsigned()->default(0);
            $table->string('bonus_name', 100)->nullable();
            $table->integer('deposit_limit')->unsigned()->default(0);
            $table->integer('rake_percent')->unsigned()->default(0);
            $table->string('bonus_code', 255)->nullable();
            $table->float('deposit_multiplier', $total = 10, $places = 2)->unsigned()->default(1);
            $table->string('bonus_type', 50)->default('casino');
            $table->boolean('exclusive')->default(1);
            $table->string('bonus_tag', 50)->nullable();
            $table->string('type', 50)->default('casino');
            $table->string('game_tags', 255)->nullable();
            $table->integer('cash_percentage')->unsigned()->default(0);
            $table->bigInteger('max_payout')->unsigned()->default(0);
            $table->string('reload_code', 50)->nullable();
            $table->string('excluded_countries', 510)->nullable();
            $table->string('included_countries', 510)->nullable();
            $table->bigInteger('deposit_amount')->unsigned()->default(0);
            $table->float('deposit_max_bet_percent', $total = 10, $places = 2);
            $table->float('bonus_max_bet_percent', $total = 10, $places = 2);
            $table->bigInteger('max_bet_amount')->nullable();
            $table->smallInteger('fail_limit')->nullable()->default(0);
            $table->string('game_percents', 255)->nullable();
            $table->float('loyalty_percent', $total = 10, $places = 2)->unsigned()->default(0);
            $table->bigInteger('top_up')->unsigned()->default(0);
            $table->float('stagger_percent', $total = 10, $places = 2)->unsigned()->default(0);
            $table->string('ext_ids', 255)->nullable();
            $table->string('progress_type', 10)->default('both');
            $table->bigInteger('deposit_threshold')->unsigned()->default(0);
            $table->string('game_id', 50)->nullable();
            $table->boolean('allow_race')->default(0);
            $table->smallInteger('frb_coins')->unsigned()->default(0);
            $table->float('frb_denomination', $total = 10, $places = 2)->unsigned()->default(0);
            $table->smallInteger('frb_lines')->unsigned()->default(0);
            $table->float('frb_cost', $total = 10, $places = 2)->unsigned()->default(0);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
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
