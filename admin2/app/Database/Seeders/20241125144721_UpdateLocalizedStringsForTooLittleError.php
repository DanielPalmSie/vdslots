<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForTooLittleError extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'da',
            'alias' => 'err.toolittle',
            'value' => 'Beløbet er for lille.',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'err.toolittle',
            'value' => 'El importe es demasiado pequeño.',
        ],
        [
            'language' => 'en',
            'alias' => 'err.toolittle',
            'value' => 'The amount is too small.',
        ],
        [
            'language' => 'sv',
            'alias' => 'err.toolittle',
            'value' => 'Beloppet är för litet',
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
        if ($this->brand === 'megariches' || $this->brand === 'dbet') {
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
        if ($this->brand === 'megariches' || $this->brand === 'dbet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => $row['value']]);
            }
        }
    }
}
