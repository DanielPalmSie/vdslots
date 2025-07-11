<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddJurisdictionPopupMessageKungaslottet extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'alias'    => 'new.jurisdiction.popup.message',
            'language' => 'en',
            'value'    => 'You are now entering a website under Maltese jurisdiction and is licensed within the EU to operate online gambling. When playing at Kungaslottet you play under the Maltese regulation authorized by the Malta Gaming Authority (https://www.mga.org.mt/).'
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
        if ($this->brand === 'kungaslottet') {

            $this->connection
                ->table($this->table)
                ->where('alias','new.jurisdiction.popup.message')
                ->where('language', 'en')
                ->delete();

            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias','new.jurisdiction.popup.message')
                ->where('language', 'en')
                ->delete();
        }
    }
}