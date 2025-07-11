<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class EnableEvaluationRG11 extends Seeder
{
    public function up()
    {
        Config::where('config_name', "RG11-evaluation-in-jurisdictions")->update([
            'config_value' => "UKGC,SGA,CAON,DGA,MGA,ADM,DGOJ"
        ]);
    }
    public function down()
    {
        Config::where('config_name', "RG11-evaluation-in-jurisdictions")->update([
            'config_value' => ""
        ]);
    }
}