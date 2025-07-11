<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringForVoucher extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'voucher.code',
            'value' => 'Voucher Code:'
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    { 
        $this->connection
            ->table($this->table)
            ->where('alias','voucher.code')
            ->where('language', 'en')
            ->delete();

        $this->connection
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias','voucher.code')
            ->where('language', 'en')
            ->delete();
    }
}