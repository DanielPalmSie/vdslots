<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForConfirmationPopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'responsible.confirm.html',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/privacy-confirmation.png"><div>Are you sure you want to perform this action?</div></div>',
        ],
        [
            'language' => 'sv',
            'alias' => 'responsible.confirm.html',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/privacy-confirmation.png"><div>&Auml;r du s&auml;ker p&aring; att du vill utf&ouml;ra den h&auml;r &aring;tg&auml;rden?</div></div>',
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
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => '<p>Are you sure you want to perform this action?</p>']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => '<p>&Auml;r du s&auml;ker p&aring; att du vill utf&ouml;ra den h&auml;r &aring;tg&auml;rden?</p>
                         <p>&nbsp;</p>']);
        }
    }
}
