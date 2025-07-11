<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsLimitsUpdatedPopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'rglimits.added.successfully',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><h3 class="popup-v2-subtitle">Limits Updated</h3>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div><div>',
        ],
        [
            'language' => 'da',
            'alias' => 'rglimits.added.successfully',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><h3 class="popup-v2-subtitle">Opdaterede grænser</h3>
                        <div>Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.</div><div>',
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.change.success.cooloff',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><h3 class="popup-v2-subtitle">Limits Updated</h3>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div><div>',
        ],
        [
            'language' => 'da',
            'alias' => 'rglimits.change.success.cooloff',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><h3 class="popup-v2-subtitle">Opdaterede grænser</h3>
                        <div>Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.</div><div>',
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.change.success.immediate',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><h3 class="popup-v2-subtitle">Limits Updated</h3>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div><div>',
        ],
        [
            'language' => 'da',
            'alias' => 'rglimits.change.success.immediate',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><h3 class="popup-v2-subtitle">Opdaterede grænser</h3>
                        <div>Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.</div><div>',
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

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[2]['alias'])
                ->where('language',$this->data[2]['language'])
                ->update(['value' => $this->data[2]['value']]);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[3]['alias'])
                ->where('language',$this->data[3]['language'])
                ->update(['value' => $this->data[3]['value']]);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[4]['alias'])
                ->where('language',$this->data[4]['language'])
                ->update(['value' => $this->data[4]['value']]);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[5]['alias'])
                ->where('language',$this->data[5]['language'])
                ->update(['value' => $this->data[5]['value']]);
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => 'Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[2]['alias'])
                ->where('language',$this->data[2]['language'])
                ->update(['value' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[3]['alias'])
                ->where('language',$this->data[3]['language'])
                ->update(['value' => 'Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[4]['alias'])
                ->where('language',$this->data[4]['language'])
                ->update(['value' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[5]['alias'])
                ->where('language',$this->data[5]['language'])
                ->update(['value' => 'Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.']);
        }
    }
}
