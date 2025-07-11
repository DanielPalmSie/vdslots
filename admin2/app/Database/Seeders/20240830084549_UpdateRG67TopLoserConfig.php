<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateRG67TopLoserConfig extends Seeder
{
    public function up()
    {
        Config::where('config_name', 'RG67-top-loser')->update([
            'config_value' => '5:GB SE;2:DK'
        ]);
    }

    public function down()
    {
        Config::where('config_name', 'RG67-top-loser')->update([
            'config_value' => '5:GB;5:SE;2:DK'
        ]);
    }
}
