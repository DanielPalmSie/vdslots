<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedErrorValuePopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'reality-check.error.value.between',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/kungaslottet/failed.png"><h6 class="popup-v2-subtitle center-stuff">Value Error</h6><div>Set a value between {{rc_min}} and {{rc_max}}</div>',
        ],
        [
            'language' => 'sv',
            'alias' => 'reality-check.error.value.between',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/kungaslottet/failed.png"><h6 class="popup-v2-subtitle center-stuff">Värde Fel</h6><div>Ange ett värde mellan {{rc_min}} och {{rc_max}}</div>',
        ]
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
                ->where('language',$this->data[0]['language'])
                ->update(['value' => 'Set a value between {{rc_min}} and {{rc_max}}']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => 'Ange ett värde mellan {{rc_min}} och {{rc_max}}']);
        }
    }
}
