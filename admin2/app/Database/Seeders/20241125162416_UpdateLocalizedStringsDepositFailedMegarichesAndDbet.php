<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsDepositFailedMegarichesAndDbet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'da',
            'alias' => 'deposit.failed.html',
            'value' => '<h3 class="popup-v2-subtitle">Indbetaling mislykkedes</h3><div>Indbetalingen er mislykket.</div>',
            'old_value' => 'Indbetalingen er mislykket.',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'deposit.failed.html',
            'value' => '<h3 class="popup-v2-subtitle">Depósito fallido</h3><div>El depósito falló.</div>',
            'old_value' => 'El depósito falló',
        ],
        [
            'language' => 'en',
            'alias' => 'deposit.failed.html',
            'value' => '<h3 class="popup-v2-subtitle">Deposit Unsuccessful</h3><div>The deposit failed.</div>',
            'old_value' => 'Balance too low.',
        ],
        [
            'language' => 'sv',
            'alias' => 'deposit.failed.html',
            'value' => '<h3 class="popup-v2-subtitle">Insättning misslyckades</h3><div>Ins&auml;ttningen misslyckades.</div>',
            'old_value' => 'Ins&auml;ttningen misslyckades.',
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
                    ->update(['value' => $row['old_value']]);
            }
        }
    }
}
