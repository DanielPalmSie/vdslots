<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForTimeoutPopupMegariches extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'lgatime.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/warning.png"><div class="popup-title center-stuff">Timeout Alert</div>
                        <p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />
                        All bets and wins that have been started will be executed. <br /><br />
                        If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>',
        ],
        [
            'language' => 'da',
            'alias' => 'lgatime.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/warning.png"><div class="popup-title center-stuff">Timeout-advarsel</div>
                        <p>Spillet er blevet afbrudt fordi din Timeout begr&aelig;nsning er blevet n&aring;et.<br /><br />
                        Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.<br /><br />
                        Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</p>',
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
                ->update(['value' => '<p>Gameplay has been interrupted because your Timeout-limit has been reached. <br /><br />
                                         All bets and wins that have been started will be executed. <br /><br />
                                         If you were in the middle of a free spins round or similar the free spins will start again the next time you launch the game.</p>']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => '<p>Spillet er blevet afbrudt fordi din Timeout begr&aelig;nsning er blevet n&aring;et.</p>
                                      <p>Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.</p>
                                      <p>Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</p>']);
        }
    }
}
