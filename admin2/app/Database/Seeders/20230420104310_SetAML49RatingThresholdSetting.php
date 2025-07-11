<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class SetAML49RatingThresholdSetting extends Seeder
{
    public function up()
    {
        DB::shBeginTransaction(true);
        $config = new Config();
        $config->fill([
            'config_name' => 'AML49-score',
            'config_tag' => 'AML',
            'config_value' => 'min:70 max:79',
            'config_type' => json_encode([
                "type" => "template",
                "delimiter" => ":",
                "next_data_delimiter" => " ",
                "format" => "<:Name><delimiter><:Number>"
            ]),
        ]);
        $config->save();
        DB::shCommit(true);
    }

    public function down()
    {
        Config::where('config_name', 'AML49-score')->delete();
    }
}