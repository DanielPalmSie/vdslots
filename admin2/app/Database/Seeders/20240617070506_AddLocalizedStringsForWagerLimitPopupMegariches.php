<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringsForWagerLimitPopupMegariches extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'lgawager.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/wager-limit-reached.png"><div class="popup-title center-stuff">Wager Limit Reached</div>
                        <div>Gameplay has been interrupted because your wager limit has been reached. <br /><br />
                        All bets and wins that have been started will be exectued. <br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</div>',
        ],
        [
            'language' => 'da',
            'alias' => 'lgawager.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/wager-limit-reached.png"><div class="popup-title center-stuff">Indsatsgrænse nået</div>
                        <div>Spillet er blevet afbrudt fordi din indsatsgr&aelig;nse er blevet n&aring;et.<br /><br />
                        Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.<br /><br />
                        Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</div>',
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
                ->update(['value' => '<p>Gameplay has been interrupted because your wager limit has been reached. <br /><br />
                                      All bets and wins that have been started will be exectued. <br /><br />
                                      If you were in the middle of a free spin or similar you will enter the free spin again next <br />
                                      time you launch the game.</p>']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => '<p>Spillet er blevet afbrudt fordi din indsatsgr&aelig;nse er blevet n&aring;et.</p>
                                      <p>Alle igangv&aelig;rende indsatser og gevinster vil blive gennemf&oslash;rt.</p>
                                      <p>Hvis du var midt i en runde med free spins eller lignende vil disse genstarte n&aelig;ste gang du &aring;bner spillet.</p>']);
        }
    }
}
