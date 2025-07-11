<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateRG27FlagDescription extends Seeder
{

    private string $table;

    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)->where('name', 'RG27')->update([
                'indicator_name' => 'Deposit from a player with High Risk',
                'description' => 'Only flag customers with RG Risk Profile rating of High Risk.'
            ]);
        }, true);
    }

    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)->where('name', 'RG27')->update([
                'indicator_name' => 'Deposit from a player with High',
                'description' => 'Only flag customers with RG Risk Profile rating between x - y in score.'
            ]);
        }, true);
    }
}