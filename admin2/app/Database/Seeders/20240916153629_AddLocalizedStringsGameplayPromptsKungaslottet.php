<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsGameplayPromptsKungaslottet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'lgatime.reached.html',
            'value' => '<h6 class="popup-v2-subtitle center-stuff">Timeout Alert</h6><p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />
                        All bets and wins that have been started will be executed. <br /><br />If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />All bets and wins that have been started will be executed. <br /><br />
                           If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'lgatime.reached.html',
            'value' => '<h6 class="popup-v2-subtitle center-stuff">Timeout Varning</h6><p>Ditt spel har avbrutits eftersom du har n&aring;tt din spelavbrottsgr&auml;ns. <br /><br />
                        Denna insats och eventuella vinster kommer att fullf&ouml;ljas.&nbsp;<br /><br />Om du var inne i Free Spins eller liknande s&aring; kommer du<br />att komma tillbaka till samma st&auml;lle n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Ditt spel har avbrutits eftersom du har n&aring;tt din spelavbrottsgr&auml;ns. <br /><br />Denna insats och eventuella vinster kommer att fullf&ouml;ljas.&nbsp;<br /><br />
                           Om du var inne i Free Spins eller liknande s&aring; kommer du<br />att komma tillbaka till samma st&auml;lle n&auml;sta g&aring;ng du startar spelet.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'lgawager.reached.html',
            'value' => '<h6 class="popup-v2-subtitle">Wager Limit Reached</h6><p>Gameplay has been interrupted because your wager limit has been reached. <br /><br />
                        All bets and wins that have been started will be executed. <br /><br />If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because your wager limit has been reached. <br /><br />
                           All bets and wins that have been started will be executed. <br /><br />If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'lgawager.reached.html',
            'value' => '<h6 class="popup-v2-subtitle">Oms&auml;ttningsgr&auml;ns uppn&aring;dd</h6><p>Spelet har avbrutits eftersom din insatsgr&auml;ns har uppn&aring;tts. <br /><br />
                        Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br />Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Spelet har avbrutits eftersom din insatsgr&auml;ns har uppn&aring;tts. <br /><br />
                           Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br />Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'betmax.reached.html',
            'value' => '<h6 class="popup-v2-subtitle">Max Bet Protection Limit Reached</h6><p>Gameplay has been interrupted because you have placed a bet or spin (includes gambling feature) which is higher than your max bet protection limit. <br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because you have placed a bet or spin (includes gambling feature) which is higher than your max bet protection limit. <br /><br />
                           If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'betmax.reached.html',
            'value' => '<h6 class="popup-v2-subtitle">Max insats skyddsgr&auml;ns uppn&aring;dd</h6><p>Spelet har avbrutits eftersom du har placerat en insats eller snurr (inkluderar spelfunktion) som &auml;r h&ouml;gre &auml;n din maxinsatsskyddsgr&auml;ns. <br /><br />
                        Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Spelet har avbrutits eftersom du har placerat en insats eller snurr (inkluderar spelfunktion) som &auml;r h&ouml;gre &auml;n din maxinsatsskyddsgr&auml;ns. <br /><br />
                           Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'lgaloss.reached.html',
            'value' => '<h6 class="popup-v2-subtitle">Loss Limit Reached</h6><p>Gameplay has been interrupted because your loss limit has been reached. <br /><br />
                        All bets and wins that have been started will be executed. <br /><br /> If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because your loss limit has been reached. <br /><br />
                           All bets and wins that have been started will be executed. <br /><br /> If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'lgaloss.reached.html',
            'value' => '<h6 class="popup-v2-subtitle">F&ouml;rlustgr&auml;ns uppn&aring;dd</h6><p>Spelet har avbrutits eftersom din f&ouml;rlustgr&auml;ns har n&aring;tts. <br /><br />
                        Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br /> Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Spelet har avbrutits eftersom din f&ouml;rlustgr&auml;ns har n&aring;tts. <br /><br />
                        Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br /> Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
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
        if ($this->brand === 'kungaslottet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => $row['oldValue']]);
            }
        }
    }
}
