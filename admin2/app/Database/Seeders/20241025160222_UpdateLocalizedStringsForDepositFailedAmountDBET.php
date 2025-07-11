<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringsForDepositFailedAmountDBET extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'da',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/dbet/failed.png"><div>Beløbet er for lille.</div></div>',
            'oldValue' => 'Beløbet er for lille.'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/dbet/failed.png"><div>El importe es demasiado pequeño.</div></div>',
            'oldValue' => 'El importe es demasiado pequeño.'
        ],
        [
            'language' => 'en',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/dbet/failed.png"><div>The amount is too small.</div></div>',
            'oldValue' => 'The amount is too small.'
        ],
        [
            'language' => 'sv',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/dbet/failed.png"><div>Beloppet är för litet.</div></div>',
            'oldValue' => 'Beloppet är för litet.'
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
        if ($this->brand === 'dbet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => $row['value']]);
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'dbet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => $row['oldValue']]);
            }
        }
    }
}
