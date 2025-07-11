<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForErrorPopupMegariches extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'reality-check.error.value.between',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/failed.png"><div class="popup-title">Value Error</div>Set a value between {{rc_min}} and {{rc_max}}',
        ],
        [
            'language' => 'da',
            'alias' => 'reality-check.error.value.between',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/failed.png"><div class="popup-title">Værdifejl</div>Sæt en værdi mellem {{rc_min}} og {{rc_max}}',
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
        if ($this->brand === 'megariches') {

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
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language', $this->data[0]['language'])
                ->update(['value' => 'Set a value between {{rc_min}} and {{rc_max}}']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language', $this->data[1]['language'])
                ->update(['value' => 'Sæt en værdi mellem {{rc_min}} og {{rc_max}}']);
        }
    }
}
