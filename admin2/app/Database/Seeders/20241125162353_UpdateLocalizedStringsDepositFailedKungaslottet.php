<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsDepositFailedKungaslottet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'deposit.failed.html',
            'value' => '<h6 class="popup-v2-subtitle">Deposit Unsuccessful</h6><div>The deposit failed.</div>',
            'old_value' => 'The deposit failed.',
        ],
        [
            'language' => 'sv',
            'alias' => 'deposit.failed.html',
            'value' => '<h6 class="popup-v2-subtitle">Insättning misslyckades</h6><div>Ins&auml;ttningen misslyckades.</div>',
            'old_value' => 'Ins&auml;ttningen misslyckades.',
        ],

    ];

    public function up()
    {
        if ($this->brand === 'kungaslottet') {
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
        if ($this->brand === 'kungaslottet') {
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
