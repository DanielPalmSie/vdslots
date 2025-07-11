<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringsForWagerLimitReachedPopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'lgawager.reached.html',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/wager-limit-reached.png"><h6 class="popup-v2-subtitle">Wager Limit Reached</h6><div>Gameplay has been interrupted because your wager limit has been reached. <br /><br />All bets and wins that have been started will be exectued. <br /><br />If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</div></div>',
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
                ->where('alias','lgawager.reached.html')
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
                ->where('alias','lgawager.reached.html')
                ->where('language', 'en')
                ->delete();
        }
    }
}
