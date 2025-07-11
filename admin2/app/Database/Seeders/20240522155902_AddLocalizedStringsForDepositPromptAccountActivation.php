<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForDepositPromptAccountActivation extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'no.deposit.msg',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/megariches/deposit-limit-setup.png"><h3 class="popup-v2-subtitle">Account Activation</h3><div>Your account will be activated when you complete your first deposit.</div></div>',
        ],
        [
            'language' => 'da',
            'alias' => 'no.deposit.msg',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/megariches/deposit-limit-setup.png"><h3 class="popup-v2-subtitle">Kontoaktivering</h3><div>Din konto vil blive aktiveret, når du gennemfører din første indbetaling.</div></div>',
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
        if ($this->brand === 'megariches') {

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => $this->data[0]['value']]);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => $this->data[1]['value']]);
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => 'Your account will be activated when you complete your first deposit.']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => 'Din konto vil blive aktiveret, når du gennemfører din første indbetaling.']);
        }
    }
}
