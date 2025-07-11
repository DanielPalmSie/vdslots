<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringsForWithdrawalFailedAmount extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/megariches/failed.png"><h3 class="popup-v2-subtitle">Withdrawal Failed</h3><div>The amount is too small.</div></div>',
        ],
        [
            'language' => 'da',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/megariches/failed.png"><h3 class="popup-v2-subtitle">Tilbagetrækningen mislykkedes</h3><div>Beløbet er for lille</div></div>',
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
                ->update(['value' => 'The amount is too small.']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => 'Beløbet er for lille']);
        }
    }
}
