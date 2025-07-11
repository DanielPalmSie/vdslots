<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForDepositFailedAmountMegariches extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'da',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/megariches/failed.png"><div>Beløbet er for lille.</div></div>',
            'oldValue' => 'Beløbet er for lille.'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/megariches/failed.png"><div>El importe es demasiado pequeño.</div></div>',
            'oldValue' => 'El importe es demasiado pequeño.'
        ],
        [
            'language' => 'en',
            'alias' => 'err.toolittle',
            'value' => '<div><img class="login-popup__image" src="/diamondbet/images/megariches/failed.png"><div>The amount is too small.</div></div>',
            'oldValue' => 'The amount is too small.'
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
        if ($this->brand === 'megariches') {
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
