<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringForAccountActivationMessageDbet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'alias'    => 'no.deposit.msg',
            'language' => 'en',
            'value'    => '<div class="account-activation"><img src="/diamondbet/images/dbet/deposit-to-play.png"><span>Account Activation</span><div>Your account will be activated when you complete your first deposit.</div></div>'
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
        if ($this->brand !== 'dbet') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where('alias',$this->data[0]['alias'])
            ->where('language',$this->data[0]['language'])
            ->update(['value' => $this->data[0]['value']]);
    }

    public function down()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where('alias',$this->data[0]['alias'])
            ->where('language',$this->data[0]['language'])
            ->update(['value' => 'Your account will be activated when you complete your first deposit.']);
    }
}
