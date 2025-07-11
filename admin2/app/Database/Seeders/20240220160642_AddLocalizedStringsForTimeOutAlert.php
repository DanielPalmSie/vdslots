<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForTimeOutAlert extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'lgatime.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/kungaslottet/max-bet-limit-reached.png"><h6 class="popup-v2-subtitle center-stuff">Timeout Alert</h6><p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />All bets and wins that have been started will be executed. <br /><br />If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'lgatime.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/kungaslottet/max-bet-limit-reached.png"><h6 class="popup-v2-subtitle center-stuff">Timeout Varning</h6><p>Ditt spel har avbrutits eftersom du har n&aring;tt din spelavbrottsgr&auml;ns. <br /><br />Denna insats och eventuella vinster kommer att fullf&ouml;ljas.&nbsp;<br /><br />Om du var inne i Free Spins eller liknande s&aring; kommer du<br />att komma tillbaka till samma st&auml;lle n&auml;sta g&aring;ng du startar spelet.</p>',
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
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => '<p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />All bets and wins that have been started will be executed. <br /><br />If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => '<p>Ditt spel har avbrutits eftersom du har n&aring;tt din spelavbrottsgr&auml;ns. <br /><br />Denna insats och eventuella vinster kommer att fullf&ouml;ljas.&nbsp;<br /><br />Om du var inne i Free Spins eller liknande s&aring; kommer du<br />att komma tillbaka till samma st&auml;lle n&auml;sta g&aring;ng du startar spelet.</p>']);
        }
    }
}
