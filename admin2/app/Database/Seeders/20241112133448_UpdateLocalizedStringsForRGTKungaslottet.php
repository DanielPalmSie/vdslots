<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForRGTKungaslottet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<h6 class="popup-v2-subtitle">Lock Game Category</h6>
                        <div>Are you sure you want to perform this action?</div>',
            'oldValue' => 'Are you sure you want to perform this action?'
        ],
        [
            'language' => 'sv',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<h6 class="popup-v2-subtitle">Kategori L&aring;sspel</h6>
                        <div>&Auml;r du s&auml;ker p&aring; att du vill utf&ouml;ra denna &aring;tg&auml;rd?</div>',
            'oldValue' => '&Auml;r du s&auml;ker p&aring; att du vill utf&ouml;ra denna &aring;tg&auml;rd?'
        ],
        [
            'language' => 'sv',
            'alias' => 'responsible.confirm.html',
            'value' => '<p>&Auml;r du s&auml;ker p&aring; att du vill utf&ouml;ra den h&auml;r &aring;tg&auml;rden?</p>',
            'oldValue' => '&Auml;r du s&auml;ker p&aring; att du vill utf&ouml;ra den h&auml;r &aring;tg&auml;rden?'
        ],
        [
            'language' => 'en',
            'alias' => 'responsible.confirm.html',
            'value' => '<p>Are you sure you want to perform this action?</p>',
            'oldValue' => 'Are you sure you want to perform this action?'
        ],
        [
            'language' => 'sv',
            'alias' => 'rglimits.added.successfully',
            'value' => '<div><h6 class="popup-v2-subtitle">Uppdaterade gränser</h6>
                        <div>Dina gränser har uppdaterats. Ökningar kommer att ske om {{cooloff_period}} dagar, medan minskningar ändras omedelbart.</div></div>',
            'oldValue' => 'Dina gränser har uppdaterats. Ökningar kommer att ske om {{cooloff_period}} dagar, medan minskningar ändras omedelbart.'
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.added.successfully',
            'value' => '<div><h6 class="popup-v2-subtitle">Limits Updated</h6>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div></div>',
            'oldValue' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.'
        ],
        [
            'language' => 'sv',
            'alias' => 'rglimits.change.success.cooloff',
            'value' => '<div><h6 class="popup-v2-subtitle">Uppdaterade gränser</h6>
                        <div>Dina gränser har uppdaterats. Ökningar kommer att ske om {{cooloff_period}} dagar, medan minskningar ändras omedelbart.</div></div>',
            'oldValue' => 'Dina gränser har uppdaterats. Ökningar kommer att ske om {{cooloff_period}} dagar, medan minskningar ändras omedelbart.'
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.change.success.cooloff',
            'value' => '<div><h6 class="popup-v2-subtitle">Limits Updated</h6>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div></div>',
            'oldValue' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.'
        ],
        [
            'language' => 'sv',
            'alias' => 'rglimits.change.success.immediate',
            'value' => '<div><h6 class="popup-v2-subtitle">Uppdaterade gränser</h6>
                        <div>Dina gränser har uppdaterats. Ökningar kommer att ske om {{cooloff_period}} dagar, medan minskningar ändras omedelbart.</div></div>',
            'oldValue' => 'Dina gränser har uppdaterats. Ökningar kommer att ske om {{cooloff_period}} dagar, medan minskningar ändras omedelbart.'
        ],
        [
            'language' => 'en',
            'alias' => 'rglimits.change.success.immediate',
            'value' => '<div><h6 class="popup-v2-subtitle">Limits Updated</h6>
                        <div>Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.</div></div>',
            'oldValue' => 'Your limits have been updated. Increases will take place in {{cooloff_period}} days, while decreases are set immediately.'
        ],
        [
            'language' => 'en',
            'alias' => 'reality-check.error.value.between',
            'value' => '<h6 class="popup-v2-subtitle">Value Error</h6><div>Set a value between {{rc_min}} and {{rc_max}}.</div>',
            'oldValue' => 'Set a value between {{rc_min}} and {{rc_max}}.'
        ],
        [
            'language' => 'sv',
            'alias' => 'reality-check.error.value.between',
            'value' => '<h6 class="popup-v2-subtitle">Värde Fel</h6><div>Ange ett värde mellan {{rc_min}} och {{rc_max}}.</div>',
            'oldValue' => 'Ange ett värde mellan {{rc_min}} och {{rc_max}}.'
        ],
        [
            'language' => 'en',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Loss Limit</h2><p>You tried to set a higher loss limit than the maximum available.</p>
                        <p>Your current loss limit has been set to <strong>{{loss_limit}}</strong> and if you wish to have a higher limit, please contact customer support.</p>',
            'oldValue' => '<p>You tried to set a higher loss limit than the maximum available.</p>
                           <p>Your current loss limit has been set to <strong>{{loss_limit}}</strong> and if you wish to have a higher limit, please contact customer support.</p>'
        ],
        [
            'language' => 'sv',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Gr&auml;ns f&ouml;r f&ouml;rlust</h2><p>Du f&ouml;rs&ouml;kte ange en h&ouml;gre f&ouml;rlustgr&auml;ns &auml;n den h&ouml;gsta tillg&auml;ngliga.</p>
                        <p>Din nuvarande f&ouml;rlustgr&auml;ns har st&auml;llts in till <strong>{{loss_limit}}</strong> och om du vill ha en h&ouml;gre gr&auml;ns, v&auml;nligen kontakta kundtj&auml;nst.</p>',
            'oldValue' => '<p>Du f&ouml;rs&ouml;kte ange en h&ouml;gre f&ouml;rlustgr&auml;ns &auml;n den h&ouml;gsta tillg&auml;ngliga.</p>
                           <p>Din nuvarande f&ouml;rlustgr&auml;ns har st&auml;llts in till <strong>{{loss_limit}}</strong> och om du vill ha en h&ouml;gre gr&auml;ns, v&auml;nligen kontakta kundtj&auml;nst.</p>'
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
