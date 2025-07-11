<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForAccountActivationPopup extends SeederTranslation
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'alias'    => 'no.deposit.msg',
            'language' => 'en',
            'value'    => '<div class="account-activation"><img src="/diamondbet/images/kungaslottet/deposit-to-play.png"><span>Account Activation</span><div>Your account will be activated when you complete your first deposit.</div></div>'
        ]
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
                ->where('alias','no.deposit.msg')
                ->where('language', 'en')
                ->delete();

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
                ->where('alias','no.deposit.msg')
                ->where('language', 'en')
                ->delete();
        }
    }
}