<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForLossLimitPopupMegarichesAndDbet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'br',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Limite de perdas</h2><p>Você tentou definir um limite de perda maior do que o máximo disponível.</p>
                        <p>Seu limite de perda atual foi definido como {{loss_limit}} e, se desejar um limite mais alto, entre em contato com o suporte ao cliente.</p>',
            'oldValue' => '<p>Você tentou definir um limite de perda maior do que o máximo disponível.</p>
                           <p>Seu limite de perda atual foi definido como {{loss_limit}} e, se desejar um limite mais alto, entre em contato com o suporte ao cliente.</p>'
        ],
        [
            'language' => 'cl',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Límite de pérdidas</h2><p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                        <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>',
            'oldValue' => '<p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                           <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>'
        ],
        [
            'language' => 'da',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Grænse for tab</h2><p>Du forsøgte at indstille en højere tabsgrænse end den maksimalt tilgængelige.</p>
                        <p>Din nuværende tabsgrænse er indstillet til {{loss_limit}}, og hvis du ønsker en højere grænse, bedes du kontakte kundesupport.</p>',
            'oldValue' => '<p>Du forsøgte at indstille en højere tabsgrænse end den maksimalt tilgængelige.</p>
                           <p>Din nuværende tabsgrænse er indstillet til {{loss_limit}}, og hvis du ønsker en højere grænse, bedes du kontakte kundesupport.</p>'
        ],
        [
            'language' => 'dgoj',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Límite de pérdidas</h2><p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                        <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>',
            'oldValue' => '<p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                           <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>'
        ],
        [
            'language' => 'en',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Loss Limit</h2><p>You tried to set a higher loss limit than the maximum available.</p>
                        <p>Your current loss limit has been set to {{loss_limit}} and if you wish to have a higher limit, please contact customer support.</p>',
            'oldValue' => '<p>You tried to set a higher loss limit than the maximum available.</p>
                           <p>Your current loss limit has been set to {{loss_limit}} and if you wish to have a higher limit, please contact customer support.</p>'
        ],
        [
            'language' => 'es',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Límite de pérdidas</h2><p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                        <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>',
            'oldValue' => '<p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                           <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>'
        ],
        [
            'language' => 'fi',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Tappion raja</h2><p>Yritit asettaa korkeamman tappiorajan kuin käytettävissä oleva enimmäisraja.</p>
                        <p>Nykyinen tappiorajasi on asetettu {{loss_limit}} ja jos haluat korkeamman rajan, ota yhteyttä asiakastukeen.</p>',
            'oldValue' => '<p>Yritit asettaa korkeamman tappiorajan kuin käytettävissä oleva enimmäisraja.</p>
                           <p>Nykyinen tappiorajasi on asetettu {{loss_limit}} ja jos haluat korkeamman rajan, ota yhteyttä asiakastukeen.</p>'
        ],
        [
            'language' => 'pe',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Límite de pérdidas</h2><p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                        <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>',
            'oldValue' => '<p>Ha intentado establecer un límite de pérdidas superior al máximo disponible.</p>
                           <p>Tu límite de pérdidas actual se ha establecido en {{loss_limit}} y si deseas un límite superior, ponte en contacto con el servicio de atención al cliente.</p>'
        ],
        [
            'language' => 'sv',
            'alias' => 'loss-limit.set.over.maximum.html',
            'value' => '<h2 class="lic-mbox-color">Förlustgräns</h2><p>Du försökte ange en högre förlustgräns än den högsta tillgängliga.</p>
                        <p>Din nuvarande förlustgräns har ställts in på {{loss_limit}} och om du vill ha en högre gräns, vänligen kontakta kundtjänst.</p>',
            'oldValue' => '<p>Du försökte ange en högre förlustgräns än den högsta tillgängliga.</p>
                           <p>Din nuvarande förlustgräns har ställts in på {{loss_limit}} och om du vill ha en högre gräns, vänligen kontakta kundtjänst.</p>'
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
        if ($this->brand === 'megariches' || $this->brand === 'dbet') {
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
        if ($this->brand === 'megariches' || $this->brand === 'dbet') {
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
