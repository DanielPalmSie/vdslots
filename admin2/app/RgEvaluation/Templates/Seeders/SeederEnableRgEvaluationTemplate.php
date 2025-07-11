<?= "<?php ";?>

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class <?= $className ?> extends Seeder
{
    private string $config_name = "<?= $this->container['trigger_name'] ?>-evaluation-in-jurisdictions";

    public function up()
    {
        Config::create([
            "config_name" => $this->config_name,
            "config_tag" => 'RG',
            "config_value" => '',
            "config_type" => json_encode([
                "type" => "template",
                "next_data_delimiter" => ",",
                "format" => "<:Jurisdictions>"
            ], JSON_THROW_ON_ERROR)
        ]);
    }

    public function down()
    {
        Config::where('config_name', $this->config_name)->delete();
    }
}
