<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;


class UpdateLocalizedStringForLossLimitReached extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'lgaloss.reached.html',
            'value' => '<div>
                            <img
                              class="popup-v2-img"
                              src="/diamondbet/images/kungaslottet/loss-limit-reached.svg"
                            >
                            <h6
                              class="popup-v2-subtitle"
                            >Loss Limit Reached</h6>

                            <div><p>Gameplay has been interrupted because your loss limit has been reached.. <br /><br /> All bets and wins that have been started will be executed. <br /><br /> If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p></div>

                            </div>',
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
                ->where('alias','lgaloss.reached.html')
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
                ->where('alias','lgaloss.reached.html')
                ->where('language', 'en')
                ->delete();
        }
    }

}
