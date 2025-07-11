<?php
use App\Extensions\Database\Seeder\Seeder;
use \App\Models\Config;

class RequireSourceOfIncomeDocumentGB extends Seeder
{
    public function up()
    {
        $config = [
            "config_name" => 'source-of-income-countries',
            "config_tag" => 'documents',
            "config_value" => '',
            "config_type" => '{"type":"ISO2", "delimiter":" "}',
        ];

        Config::shs()->insert($config);
    }
}
