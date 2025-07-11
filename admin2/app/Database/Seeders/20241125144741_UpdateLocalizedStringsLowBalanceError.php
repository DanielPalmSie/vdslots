<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsLowBalanceError extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'da',
            'alias' => 'err.lowbalance',
            'value' => '<h3 class="popup-v2-subtitle">Tilbagetrækningen mislykkedes</h3><div>Balancen er for lav.</div>',
            'old_value' => 'Balancen er for lav.',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'err.lowbalance',
            'value' => '<h3 class="popup-v2-subtitle">Retirada fallida</h3><div>Saldo demasiado bajo.</div>',
            'old_value' => 'Saldo demasiado bajo.',
        ],
        [
            'language' => 'en',
            'alias' => 'err.lowbalance',
            'value' => '<h3 class="popup-v2-subtitle">Withdrawal Failed</h3><div>Balance too low.</div>',
            'old_value' => 'Balance too low.',
        ],
        [
            'language' => 'sv',
            'alias' => 'err.lowbalance',
            'value' => '<h3 class="popup-v2-subtitle">Uttag Misslyckades</h3><div>Balans för låg.</div>',
            'old_value' => 'Balans för låg.',
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
