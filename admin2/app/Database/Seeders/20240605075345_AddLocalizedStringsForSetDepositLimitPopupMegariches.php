<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForSetDepositLimitPopupMegariches extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'rg.info.limits.set.title',
            'value' => '<img class="popup-v2-img hidden-force mobile-display-block-force" src="/diamondbet/images/megariches/deposit-limit-setup.png"><h6 class="popup-v2-subtitle center-stuff">Set your deposit limit</h6>',
        ],
        [
            'language' => 'da',
            'alias' => 'rg.info.limits.set.title',
            'value' => '<img class="popup-v2-img hidden-force mobile-display-block-force" src="/diamondbet/images/megariches/deposit-limit-setup.png"><h6 class="popup-v2-subtitle center-stuff">Fastsæt indbetalingsgrænse</h6>',
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
                ->where('language',$this->data[0]['language'])
                ->update(['value' => 'Set your deposit limit']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => 'Fastsæt indbetalingsgrænse']);
        }
    }
}
