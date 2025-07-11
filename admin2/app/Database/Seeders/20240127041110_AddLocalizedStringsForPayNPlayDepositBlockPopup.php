<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringsForPayNPlayDepositBlockPopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'paynplay.error.deposit-block.title',
            'value' => 'Transaction Failed',
        ],
        [
            'language' => 'en',
            'alias' => 'paynplay.error.deposit-block.description',
            'value' => '<p>You are currently being prevented&nbsp;from making any further deposits. Please contact our Support Team at <a href="/customer-service/">{{supportemail}}</a> for more information.&nbsp;</p>',
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
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->whereIn('alias',['paynplay.error.deposit-block.title', 'paynplay.error.deposit-block.description'])
                ->where('language', 'en')
                ->delete();
        }
    }
}
