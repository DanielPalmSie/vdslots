<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateUKGCAffordabilityCheckConfig extends Seeder
{
    private string $old_config_name;
    private string $new_config_name;

    public function init()
    {
        $this->old_config_name = '500-affordability-check-GB';
        $this->new_config_name = 'affordability-check-UKGC';
    }

    public function up()
    {
        Config::where('config_name', $this->old_config_name)
            ->where('config_tag', 'net-deposit-limit')
            ->update(['config_name' => $this->new_config_name]);
    }

    public function down()
    {
        Config::where('config_name', $this->new_config_name)
            ->where('config_tag', 'net-deposit-limit')
            ->update(['config_name' => $this->old_config_name]);
    }
}
