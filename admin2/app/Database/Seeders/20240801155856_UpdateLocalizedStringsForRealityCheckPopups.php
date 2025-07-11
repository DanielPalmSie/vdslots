<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringsForRealityCheckPopups extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'reality-check.label.title',
            'value' => '<h4>Reality Checks</h4>',
        ],
        [
            'language' => 'sv',
            'alias' => 'reality-check.label.title',
            'value' => '<h4>Hur ofta vill du få information om ditt spelresultat?</h4>',
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if (in_array($this->brand, ['kungaslottet', 'megariches', 'dbet'])) {

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => $this->data[0]['value']]);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => $this->data[1]['value']]);
        }
    }

    public function down()
    {
        if (in_array($this->brand, ['kungaslottet', 'megariches', 'dbet'])) {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => 'Reality Checks']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => 'Hur ofta vill du få information om ditt spelresultat?']);
        }
    }
}
