<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringsForMaxBetProtectionPopupMegariches extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'betmax.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/warning.png"><div class="popup-title center-stuff">Max Bet Protection Limit Reached</div>
                        <div>Gameplay has been interrupted because you have placed a bet or spin (includes gambling feature) which is higher than your max bet protection limit.<br /><br />
                        If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</div>',
        ],
        [
            'language' => 'da',
            'alias' => 'betmax.reached.html',
            'value' => '<img class="popup-v2-img" src="/diamondbet/images/megariches/warning.png"><div class="popup-title center-stuff">Grænse for maksimal indsatsbeskyttelse nået</div>
                        <div>Spillet er blevet afbrudt, da du har placeret et v&aelig;ddem&aring;l eller spin ( inklusiv gambling funktionen ), der overstiger din maksimum beskyttelsgr&aelig;nse.<br /><br />
                        Hvis du var midt i et free spin eller lignende, vil du komme tilbage til free spin n&aelig;ste gang du starter spillet.</div>',
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
                ->update(['value' => '<p>Gameplay has been interrupted because you have placed a bet or spin (includes gambling feature) which is higher than your max bet protection limit.
                                      <br /><br />If you were in the middle of a free spin or similar you will enter the free spin again next time you launch the game.</p>']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => '<p>Spillet er blevet afbrudt, da du har placeret et v&aelig;ddem&aring;l eller spin ( inklusiv gambling funktionen ), der overstiger din maksimum beskyttelsgr&aelig;nse.</p>
                                      <p>Hvis du var midt i et free spin eller lignende, vil du komme tilbage til free spin n&aelig;ste gang du starter spillet.</p>']);
        }
    }
}
