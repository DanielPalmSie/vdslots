<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsGameplayPromptsDbet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'da',
            'alias' => 'lgatime.reached.html',
            'value' => '<p>Spillet er blevet afbrudt fordi din Timeout begr&aelig;nsning er blevet n&aring;et.<br /><br />
                        Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.<br /><br />
                        Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</p>',
            'oldValue' => '<p>Spillet er blevet afbrudt fordi din Timeout begr&aelig;nsning er blevet n&aring;et.<br /><br />
                           Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.<br /><br />
                           Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</p>',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'lgatime.reached.html',
            'value' => '<p>El juego se ha interrumpido porque se ha alcanzado el límite de tiempo. <br /><br />
                        Todas las apuestas y ganancias que se hayan iniciado se ejecutarán. <br /><br />
                        Si estabas en medio de una ronda de tiradas gratuitas o similar, las tiradas gratuitas volverán a empezar la próxima vez que inicies el juego.</p>',
            'oldValue' => '<p>El juego se ha interrumpido porque se ha alcanzado el límite de tiempo. <br /><br />
                           Todas las apuestas y ganancias que se hayan iniciado se ejecutarán. <br /><br />
                           Si estabas en medio de una ronda de tiradas gratuitas o similar, las tiradas gratuitas volverán a empezar la próxima vez que inicies el juego.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'lgatime.reached.html',
            'value' => '<h3 class="popup-title">Timeout Alert</h3>
                        <p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />
                        All bets and wins that have been started will be executed. <br /><br />
                        If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />
                           All bets and wins that have been started will be executed. <br /><br />
                           If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'lgatime.reached.html',
            'value' => '<h3 class="popup-title">Timeout Varning</h3>
                        <p>Ditt spel har avbrutits eftersom du har n&aring;tt din spelavbrottsgr&auml;ns. <br /><br />
                        Denna insats och eventuella vinster kommer att fullf&ouml;ljas.&nbsp;<br /><br />
                        Om du var inne i Free Spins eller liknande s&aring; kommer du<br />att komma tillbaka till samma st&auml;lle n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Ditt spel har avbrutits eftersom du har n&aring;tt din spelavbrottsgr&auml;ns. <br /><br />
                        Denna insats och eventuella vinster kommer att fullf&ouml;ljas.&nbsp;<br /><br />
                        Om du var inne i Free Spins eller liknande s&aring; kommer du<br />att komma tillbaka till samma st&auml;lle n&auml;sta g&aring;ng du startar spelet.</p>',
        ],
        [
            'language' => 'da',
            'alias' => 'lgawager.reached.html',
            'value' => '<p>Spillet er blevet afbrudt fordi din indsatsgr&aelig;nse er blevet n&aring;et.<br /><br />
                        Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.<br /><br />
                        Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</p>',
            'oldValue' => '<p>Spillet er blevet afbrudt fordi din indsatsgr&aelig;nse er blevet n&aring;et.<br /><br />
                           Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.<br /><br />
                           Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</p>',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'lgawager.reached.html',
            'value' => '<p>El juego se ha interrumpido porque se ha alcanzado el límite de apuesta. <br /><br />
                        Todas las apuestas y ganancias que se hayan iniciado se ejecutarán. <br /><br />
                        Si estabas en medio de una tirada gratuita o similar, volverás a entrar en la tirada gratuita la próxima vez que inicies el juego.</p>',
            'oldValue' => '<p>El juego se ha interrumpido porque se ha alcanzado el límite de apuesta. <br /><br />
                           Todas las apuestas y ganancias que se hayan iniciado se ejecutarán. <br /><br />
                           Si estabas en medio de una tirada gratuita o similar, volverás a entrar en la tirada gratuita la próxima vez que inicies el juego.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'lgawager.reached.html',
            'value' => '<h3 class="popup-title">Wager Limit Reached</h3>
                        <p>Gameplay has been interrupted because your wager limit has been reached. <br /><br />
                        All bets and wins that have been started will be exectued. <br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because your wager limit has been reached. <br /><br />
                           All bets and wins that have been started will be exectued. <br /><br />
                           If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'lgawager.reached.html',
            'value' => '<h3 class="popup-title">Oms&auml;ttningsgr&auml;ns uppn&aring;dd</h3>
                        <p>Spelet har avbrutits eftersom din insatsgr&auml;ns har uppn&aring;tts. <br /><br />
                        Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br />
                        Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Spelet har avbrutits eftersom din insatsgr&auml;ns har uppn&aring;tts. <br /><br />
                        Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br />
                        Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
        ],
        [
            'language' => 'da',
            'alias' => 'betmax.reached.html',
            'value' => '<p>Spillet er blevet afbrudt, da du har placeret et v&aelig;ddem&aring;l eller spin ( inklusiv gambling funktionen ), der overstiger din maksimum beskyttelsgr&aelig;nse.<br /><br />
                        Hvis du var midt i et free spin eller lignende, vil du komme tilbage til free spin n&aelig;ste gang du starter spillet.</p>',
            'oldValue' => '<p>Spillet er blevet afbrudt, da du har placeret et v&aelig;ddem&aring;l eller spin ( inklusiv gambling funktionen ), der overstiger din maksimum beskyttelsgr&aelig;nse.<br /><br />
                           Hvis du var midt i et free spin eller lignende, vil du komme tilbage til free spin n&aelig;ste gang du starter spillet.</p>',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'betmax.reached.html',
            'value' => '<p>El juego se ha interrumpido porque has realizado una apuesta o tirada (incluye función de juego) superior a tu límite de protección de apuesta máxima.<br /><br />
                        Si estabas en medio de una tirada gratuita o similar, volverás a entrar en la tirada gratuita la próxima vez que inicies el juego.</p>',
            'oldValue' => '<p>El juego se ha interrumpido porque has realizado una apuesta o tirada (incluye función de juego) superior a tu límite de protección de apuesta máxima.<br /><br />
                           Si estabas en medio de una tirada gratuita o similar, volverás a entrar en la tirada gratuita la próxima vez que inicies el juego.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'betmax.reached.html',
            'value' => '<h3 class="popup-title">Max Bet Protection Limit Reached</h3>
                        <p>Gameplay has been interrupted because you have placed a bet or spin (includes gambling feature) which is higher than your max bet protection limit.<br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because you have placed a bet or spin (includes gambling feature) which is higher than your max bet protection limit.<br /><br />
                           If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'betmax.reached.html',
            'value' => '<h3 class="popup-title">Max insats skyddsgr&auml;ns uppn&aring;dd</h3>
                        <p>Spelet har avbrutits eftersom du har placerat en insats eller snurr (inkluderar spelfunktion) som &auml;r h&ouml;gre &auml;n din maxinsatsskyddsgr&auml;ns. <br /><br />
                        Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Spelet har avbrutits eftersom du har placerat en insats eller snurr (inkluderar spelfunktion) som &auml;r h&ouml;gre &auml;n din maxinsatsskyddsgr&auml;ns. <br /><br />
                           Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
        ],
        [
            'language' => 'da',
            'alias' => 'lgaloss.reached.html',
            'value' => '<p>Spillet er blevet afbrudt, da du har n&aring;et din tabsgr&aelig;nse.<br /><br />
                        Alle p&aring;begyndte indsatser og gevinster vil blive fuldf&oslash;rt.<br /><br />
                        Hvis du var midt i en runde med free spins eller lignende, s&aring; vil runden forts&aelig;tte, n&aelig;ste gang du starter spillet.</p>',
            'oldValue' => '<p>Spillet er blevet afbrudt, da du har n&aring;et din tabsgr&aelig;nse.<br /><br />
                           Alle p&aring;begyndte indsatser og gevinster vil blive fuldf&oslash;rt.<br /><br />
                           Hvis du var midt i en runde med free spins eller lignende, s&aring; vil runden forts&aelig;tte, n&aelig;ste gang du starter spillet.</p>',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'lgaloss.reached.html',
            'value' => '<p>El juego se ha interrumpido porque se ha alcanzado el límite de pérdidas.<br /><br />
                        Se ejecutarán todas las apuestas y ganancias que se hayan iniciado.<br /><br />
                        Si estabas en medio de una tirada gratuita o similar, volverás a entrar en la tirada gratuita la próxima vez que inicies el juego.</p>',
            'oldValue' => '<p>El juego se ha interrumpido porque se ha alcanzado el límite de pérdidas.<br /><br />
                           Se ejecutarán todas las apuestas y ganancias que se hayan iniciado.<br /><br />
                           Si estabas en medio de una tirada gratuita o similar, volverás a entrar en la tirada gratuita la próxima vez que inicies el juego.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'lgaloss.reached.html',
            'value' => '<h3 class="popup-title">Loss Limit Reached</h3>
                        <p>Gameplay has been interrupted because your loss limit has been reached.<br /><br />
                        All bets and wins that have been started will be executed.<br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
            'oldValue' => '<p>Gameplay has been interrupted because your loss limit has been reached.<br /><br />
                        All bets and wins that have been started will be executed.<br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'lgaloss.reached.html',
            'value' => '<h3 class="popup-title">F&ouml;rlustgr&auml;ns uppn&aring;dd</h3>
                        <p>Spelet har avbrutits eftersom din f&ouml;rlustgr&auml;ns har n&aring;tts. <br /><br />
                        Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br />
                        Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
            'oldValue' => '<p>Spelet har avbrutits eftersom din f&ouml;rlustgr&auml;ns har n&aring;tts. <br /><br />
                        Alla satsningar och vinster som har p&aring;b&ouml;rjats kommer att genomf&ouml;ras. <br /><br />
                        Om du var mitt i ett gratissnurr eller liknande kommer du att g&aring; in i gratissnurret igen n&auml;sta g&aring;ng du startar spelet.</p>',
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
        if ($this->brand === 'dbet') {
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
        if ($this->brand === 'dbet') {
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
