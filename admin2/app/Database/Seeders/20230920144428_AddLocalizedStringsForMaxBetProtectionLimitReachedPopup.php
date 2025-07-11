<?php
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForMaxBetProtectionLimitReachedPopup extends SeederTranslation
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'betmax.reached.html',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/max-bet-limit-reached.png"><h6 class="popup-v2-subtitle">Max Bet Protection Limit Reached</h6><div><p>Gameplay has been interrupted because you have placed a bet or spin (includes gambling feature) which is higher than your max bet protection limit. <br /><br />If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p></div></div>',
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
            // removing it for all languages before updating the data
            $this->connection
                ->table($this->table)
                ->where('alias','betmax.reached.html')
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
                ->where('alias','betmax.reached.html')
                ->where('language', 'en')
                ->delete();
        }
    }
}
