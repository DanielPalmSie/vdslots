<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class BeBettorVulnerableNDLHighRisk extends Seeder
{
    private string $original_name = 'bebettor-vs-score-vulnerable';
    private string $new_name = 'bebettor-vs-score-vulnerable-highrisk';

    public function up()
    {
        Config::where('config_name', $this->original_name)->update(['config_name' => $this->new_name]);
    }

    public function down()
    {
        Config::where('config_name', $this->original_name)->update(['config_name' => $this->original_name]);
    }
}
