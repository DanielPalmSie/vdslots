<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class EnableEvaluationForRG8 extends Seeder
{
    public function up()
    {
        Config::where('config_name', "RG8-evaluation-in-jurisdictions")->update([
            'config_value' => "UKGC,SGA,CAON,DGA,MGA,ADM,DGOJ"
        ]);
    }
    public function down()
    {
        Config::where('config_name', "RG8-evaluation-in-jurisdictions")->update([
            'config_value' => ""
        ]);
    }
}