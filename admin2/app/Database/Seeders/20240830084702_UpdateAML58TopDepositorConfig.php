<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateAML58TopDepositorConfig extends Seeder
{
    public function up()
    {
        Config::where('config_name', 'AML58-top-depositor')->update([
            'config_value' => '5:SE GB;2:DK'
        ]);
    }

    public function down()
    {
        Config::where('config_name', 'AML58-top-depositor')->update([
            'config_value' => '5:SE;5:GB;2:DK'
        ]);
    }
}
