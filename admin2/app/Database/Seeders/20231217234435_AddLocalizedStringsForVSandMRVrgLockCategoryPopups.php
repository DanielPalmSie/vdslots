<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringsForVSandMRVrgLockCategoryPopups extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'br',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>Você tem certeza que quer fazer isso?</p>',
        ],
        [
            'language' => 'cl',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>&iquest;Est&aacute;s seguro de querer llevar a cabo esta acci&oacute;n?</p>',
        ],
        [
            'language' => 'da',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>Er du sikker p&aring;, at du &oslash;nsker, at udf&oslash;re denne handling?</p>',
        ],
        [
            'language' => 'de',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p style="text-align: justify;">Bist du dir sicher, dass du diese Aktion durchf&uuml;hren m&ouml;chtest?</p>',
        ],
        [
            'language' => 'dgoj',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>&iquest;Est&aacute;s seguro de querer llevar a cabo esta acci&oacute;n?</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>Are you sure you want to perform this action?</p>',
        ],
        [
            'language' => 'es',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>&iquest;Est&aacute;s seguro de querer llevar a cabo esta acci&oacute;n?</p>',
        ],
        [
            'language' => 'fi',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>Oletko varma ett&auml; haluat suorittaa t&auml;m&auml;n toimenpiteen?</p>',
        ],
        [
            'language' => 'hi',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>क्या आप वाकई यह क्रिया करना चाहते हैं? </p>',
        ],
        [
            'language' => 'it',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>Sei sicuro di voler eseguire questa azione?&nbsp;</p>',
        ],
        [
            'language' => 'ja',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>実行してもよろしいですか?</p>',
        ],
        [
            'language' => 'no',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>Er du sikker p&aring; at du vil gjennomf&oslash;re denne handlingen?&nbsp;</p>',
        ],
        [
            'language' => 'pe',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>&iquest;Est&aacute;s seguro de querer llevar a cabo esta acci&oacute;n?</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<p>&Auml;r du s&auml;ker p&aring; att du vill utf&ouml;ra den h&auml;r &aring;tg&auml;rden?</p><p>&nbsp;</p>',
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
        if ($this->brand !== 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias', 'rg.lock-category-popup.html')
                ->delete();

            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        if ($this->brand !== 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias', 'rg.lock-category-popup.html')
                ->delete();
        }
    }
}
