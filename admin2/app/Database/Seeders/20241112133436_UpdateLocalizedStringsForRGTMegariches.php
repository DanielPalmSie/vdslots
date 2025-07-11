<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForRGTMegariches extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'da',
            'alias' => 'responsible.confirm.html',
            'value' => '<p>Er du sikker p&aring;, at du &oslash;nsker, at udf&oslash;re denne handling?</p>',
            'oldValue' => 'Er du sikker p&aring;, at du &oslash;nsker, at udf&oslash;re denne handling?'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'responsible.confirm.html',
            'value' => '<p>¿Está seguro de que desea realizar esta acción?</p>',
            'oldValue' => '¿Está seguro de que desea realizar esta acción?'
        ],
        [
            'language' => 'en',
            'alias' => 'responsible.confirm.html',
            'value' => '<p>Are you sure you want to perform this action?</p>',
            'oldValue' => 'Are you sure you want to perform this action?'
        ],
        [
            'language' => 'da',
            'alias' => 'rglimits.added.successfully',
            'value' => '<div><h3 class="popup-v2-subtitle">Opdaterede grænser</h3>
                        <div>Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.</div></div>',
            'oldValue' => 'Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'rglimits.added.successfully',
            'value' => '<div><h3 class="popup-v2-subtitle">Límites actualizados</h3>
                        <div>Tus límites han sido actualizados. Los aumentos tendrán lugar en {{cooloff_period}} días, mientras que las disminuciones se establecerán inmediatamente.</div></div>',
            'oldValue' => 'Tus límites han sido actualizados. Los aumentos tendrán lugar en {{cooloff_period}} días, mientras que las disminuciones se establecerán inmediatamente.'
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.added.successfully',
            'value' => '<div><h3 class="popup-v2-subtitle">Limits Updated</h3>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div></div>',
            'oldValue' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.'
        ],
        [
            'language' => 'da',
            'alias' => 'rglimits.change.success.cooloff',
            'value' => '<div><h3 class="popup-v2-subtitle">Opdaterede grænser</h3>
                        <div>Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.</div></div>',
            'oldValue' => 'Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'rglimits.change.success.cooloff',
            'value' => '<div><h3 class="popup-v2-subtitle">Límites actualizados</h3>
                        <div>Tus límites han sido actualizados. Los aumentos tendrán lugar en {{cooloff_period}} días, mientras que las disminuciones se establecerán inmediatamente.</div></div>',
            'oldValue' => 'Tus límites han sido actualizados. Los aumentos tendrán lugar en {{cooloff_period}} días, mientras que las disminuciones se establecerán inmediatamente.'
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.change.success.cooloff',
            'value' => '<div><h3 class="popup-v2-subtitle">Limits Updated</h3>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div></div>',
            'oldValue' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately'
        ],
        [
            'language' => 'da',
            'alias' => 'rglimits.change.success.immediate',
            'value' => '<div><h3 class="popup-v2-subtitle">Opdaterede grænser</h3>
                        <div>Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.</div></div>',
            'oldValue' => 'Dine grænser er blevet opdateret. Forøgelse vil være aktiv om {{cooloff_period}} dage, mens sænkning vil være aktiv øjeblikkeligt.'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'rglimits.change.success.immediate',
            'value' => '<div><h3 class="popup-v2-subtitle">Límites actualizados</h3>
                        <div>Tus límites han sido actualizados. Los aumentos tendrán lugar en {{cooloff_period}} días, mientras que las disminuciones se establecerán inmediatamente.</div></div>',
            'oldValue' => 'Tus límites han sido actualizados. Los aumentos tendrán lugar en {{cooloff_period}} días, mientras que las disminuciones se establecerán inmediatamente.'
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.change.success.immediate',
            'value' => '<div><h3 class="popup-v2-subtitle">Limits Updated</h3>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div></div>',
            'oldValue' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.'
        ],
        [
            'language' => 'da',
            'alias' => 'reality-check.error.value.between',
            'value' => '<div class="popup-v2-subtitle">Værdifejl</div><div>Sæt en værdi mellem {{rc_min}} og {{rc_max}}.</div>',
            'oldValue' => 'Sæt en værdi mellem {{rc_min}} og {{rc_max}}.'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'reality-check.error.value.between',
            'value' => '<div class="popup-v2-subtitle">Error de valor</div><div>Establezca un valor entre {{rc_min}} y {{rc_max}}.</div>',
            'oldValue' => 'Establezca un valor entre {{rc_min}} y {{rc_max}}.'
        ],
        [
            'language' => 'en',
            'alias' => 'reality-check.error.value.between',
            'value' => '<div class="popup-v2-subtitle">Value Error</div><div>Set a value between {{rc_min}} and {{rc_max}}.</div>',
            'oldValue' => 'Set a value between {{rc_min}} and {{rc_max}}.'
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
        if ($this->brand === 'megariches') {
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
