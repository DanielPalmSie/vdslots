<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRgCustomerDepositLast30DaysConfig extends Seeder
{
    private array $config;

    public function init()
    {
        $jurisdictions = 'UKGC,SGA,DGA,DGOJ,ADM,AGCO,MGA';
        $this->config = [
            "config_name" => 'customer-deposit-last-30-days',
            "config_tag" => 'RG',
            "config_value" => "250::{$jurisdictions}",
            "config_type" => '{"type":"template", "delimiter":"::", "next_data_delimiter":";", "format":"<:Limit><delimiter><:Jurisdiction>"}',
        ];
    }

    public function up()
    {
        Config::create($this->config);
    }

    public function down()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }
}
