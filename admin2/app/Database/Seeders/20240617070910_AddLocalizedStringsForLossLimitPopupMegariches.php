<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringsForLossLimitPopupMegariches extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'lgaloss.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/warning.png"><div class="popup-title center-stuff">Loss Limit Reached</div>
                        <div>Gameplay has been interrupted because your loss limit has been reached.<br /><br />
                        All bets and wins that have been started will be executed.<br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</div>',
        ],
        [
            'language' => 'da',
            'alias' => 'lgaloss.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/warning.png"><div class="popup-title center-stuff">Tabsgrænse nået</div>
                        <div>Spillet er blevet afbrudt, da du har n&aring;et din tabsgr&aelig;nse.<br /><br />
                        Alle p&aring;begyndte indsatser og gevinster vil blive fuldf&oslash;rt.<br /><br />
                        Hvis du var midt i en runde med free spins eller lignende, s&aring; vil runden forts&aelig;tte, n&aelig;ste gang du starter spillet.</div>',
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
                ->update(['value' => '<p>Gameplay has been interrupted because your loss limit has been reached.<br /><br />All bets and wins that have been started will be executed.
                                      <br /><br />If you were in the middle of a free spin or similar you will enter<br /> the free spin again next time you launch the game.</p>']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => '<p>Spillet er blevet afbrudt, da du har n&aring;et din tabsgr&aelig;nse.</p>
                                      <p>Alle p&aring;begyndte indsatser og gevinster vil blive fuldf&oslash;rt.</p>
                                      <p>Hvis du var midt i en runde med free spins eller lignende, <br />s&aring; vil runden forts&aelig;tte, n&aelig;ste gang du starter spillet.</p>']);
        }
    }
}
