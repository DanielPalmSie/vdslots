<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForSetRealityCheckPopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'reality-check.label.title',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/kungaslottet/set-time.png"><h6 class="popup-v2-subtitle center-stuff">How often do you want to get information about your game performance?</h6>',
        ],
        [
            'language' => 'sv',
            'alias' => 'reality-check.label.title',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/kungaslottet/set-time.png"><h6 class="popup-v2-subtitle center-stuff">Hur ofta vill du få information om ditt spelresultat?</h6>',
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
        if ($this->brand === 'kungaslottet') {

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
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language', $this->data[0]['language'])
                ->update(['value' => 'Reality Checks']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language', $this->data[1]['language'])
                ->update(['value' => 'Hur ofta vill du få information om ditt spelresultat?']);
        }
    }

}
